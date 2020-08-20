<?php
/**
 * Copyright 2020 Martin Neundorfer (Neunerlei)
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * Last modified: 2020.07.06 at 22:19
 */

declare(strict_types=1);


namespace Neunerlei\Configuration\Finder;


use AppendIterator;
use Iterator;
use Laminas\File\ClassFileLocator;
use Neunerlei\Configuration\Event\ConfigHandlerFilterEvent;
use Neunerlei\Configuration\Exception\HandlerClassNotAutoloadableException;
use Neunerlei\Configuration\Handler\ConfigHandlerInterface;
use Neunerlei\Configuration\Handler\HandlerConfigurator;
use Neunerlei\Configuration\Handler\HandlerDefinition;
use Neunerlei\Configuration\Loader\LoaderContext;
use Neunerlei\Configuration\Util\IntuitiveTopSorter;
use Neunerlei\Configuration\Util\LocationIteratorTrait;
use Neunerlei\PathUtil\Path;

class HandlerFinder implements HandlerFinderInterface
{
    use LocationIteratorTrait;
    
    /**
     * @inheritDoc
     */
    public function find(LoaderContext $loaderContext): array
    {
        // Find the definitions
        $definitions = $this->findDefinitions($loaderContext);
        
        // Apply handler overrides
        $definitions = $this->applyHandlerOverrides($definitions);
        
        // Order the handlers by their dependencies
        $definitions = $this->sortDefinitions($definitions);
        
        // Allow filtering
        $loaderContext->dispatchEvent(($e = new ConfigHandlerFilterEvent(
            $loaderContext, $definitions
        )));
        $definitions = $e->getHandlers();
        
        return $definitions;
    }
    
    /**
     * Finds the list of all definitions based on the given handler instances and the classes
     * found in the given handler locations
     *
     * @param   \Neunerlei\Configuration\Loader\LoaderContext  $loaderContext
     *
     * @return array
     */
    protected function findDefinitions(LoaderContext $loaderContext): array
    {
        /** @var \Neunerlei\Configuration\Handler\HandlerDefinition[] $definitions */
        $definitions = [];
        
        // Helper to generate the handler config object
        $makeNewDefinition = static function (
            string $handlerClass,
            ConfigHandlerInterface $handler
        ): HandlerDefinition {
            $definition            = new HandlerDefinition();
            $definition->className = $handlerClass;
            $definition->handler   = $handler;
            $configurator          = new HandlerConfigurator($definition);
            $handler->configure($configurator);
            
            return $definition;
        };
        
        // Load static handlers
        foreach ($loaderContext->handlers as $handler) {
            $handlerClass               = get_class($handler);
            $definitions[$handlerClass] = $makeNewDefinition($handlerClass, $handler);
        }
        
        // Load handlers based on registered root paths
        foreach ($this->findHandlerClasses($loaderContext) as $handlerClass) {
            // Ignore existing handlers
            if (isset($definitions[$handlerClass])) {
                continue;
            }
            
            // Create the handler instance
            /** @var ConfigHandlerInterface $handler */
            $handler                    = $loaderContext->getInstance($handlerClass);
            $definitions[$handlerClass] = $makeNewDefinition($handlerClass, $handler);
        }
        
        return $definitions;
    }
    
    /**
     * Returns the list of all handler classes that have been found
     * in the registered handler locations
     *
     * @param   \Neunerlei\Configuration\Loader\LoaderContext  $loaderContext
     *
     * @return array
     */
    protected function findHandlerClasses(LoaderContext $loaderContext): array
    {
        // Find the list of handler classes in our registered handler locations
        $handlers           = [];
        $scannedDirectories = [];
        foreach ($this->prepareHandlerLocations($loaderContext) as $location) {
            /** @var \SplFileInfo $location */
            
            // Extract the directory from files and avoid duplicate scans
            $directory = $location->isDir() ? $location->getPathname() : $location->getPath();
            if (in_array($directory, $scannedDirectories, true)) {
                continue;
            }
            $scannedDirectories[] = $directory;
            
            // Scan for classes
            foreach (new ClassFileLocator($directory) as $class) {
                /** @var \Laminas\File\PhpClassFile $class */
                $handlers[] = array_filter($class->getClasses(), static function (string $className): bool {
                    // Check if the class can be loaded
                    if (! class_exists($className)) {
                        // Ignore interfaces and traits
                        if (interface_exists($className) || trait_exists($className)) {
                            return false;
                        }
                        
                        throw new HandlerClassNotAutoloadableException(
                            'The handler class "' . $className . '" is auto-loadable!');
                    }
                    
                    return in_array(ConfigHandlerInterface::class, class_implements($className), true);
                });
            }
        }
        
        return array_merge([], ...$handlers);
    }
    
    /**
     * Collects the list of all locations where we should search for handler classes
     *
     * @param   \Neunerlei\Configuration\Loader\LoaderContext  $loaderContext
     *
     * @return \Iterator|\FilesystemIterator
     */
    protected function prepareHandlerLocations(LoaderContext $loaderContext): Iterator
    {
        $preparedHandlerLocations = new AppendIterator();
        foreach ($loaderContext->handlerLocations as $location) {
            if (is_string($location)) {
                // Append an absolute path
                if (Path::isAbsolute($location)) {
                    $preparedHandlerLocations->append($this->prepareLocationIterator($location));
                }
                
                // Build the for a relative directory
                foreach ($loaderContext->rootLocations as $rootLocation) {
                    $rootPath = $rootLocation->getPathname();
                    $path     = Path::join($rootPath, $location);
                    $preparedHandlerLocations->append($this->prepareLocationIterator($path));
                }
            } else {
                // Append the iterator
                $preparedHandlerLocations->append($this->prepareLocationIterator($location));
            }
        }
        
        return $preparedHandlerLocations;
    }
    
    /**
     * Applies the registered handler overrides and makes sure
     * that the dependency lists are correctly rewritten based on the overrides
     *
     * @param   \Neunerlei\Configuration\Handler\HandlerDefinition[]  $definitions
     *
     * @return array
     */
    protected function applyHandlerOverrides(array $definitions): array
    {
        // Sort the list of handlers based on their overrides as "dependencies"
        // To make sure even overrides can be overridden again
        $knownClasses = array_keys($definitions);
        
        // Remove all classes that override something that does not exist
        $definitionsToRemove = [];
        $sorter              = new IntuitiveTopSorter($knownClasses);
        foreach ($definitions as $classname => $definition) {
            // Ignore if this config does not override something
            if (empty($definition->overrides)) {
                continue;
            }
            
            // Remove all dependencies that are not in the list of known classes
            $dependencies = array_filter(
                $definition->overrides,
                static function (string $classname) use ($knownClasses) {
                    return in_array($classname, $knownClasses, true);
                });
            
            // Check if we still can override something
            if (! empty($dependencies)) {
                foreach ($dependencies as $dependency) {
                    $sorter->moveItemAfter($classname, $dependency);
                }
                continue;
            }
            
            // Mark this definition as to be removed
            $definitionsToRemove[] = $classname;
        }
        
        // Sort and filter the definitions
        $definitionsSortedAndFiltered = [];
        foreach ($sorter->sort() as $sortedClass) {
            // Skip removed definitions
            if (in_array($sortedClass, $definitionsToRemove, true)) {
                continue;
            }
            $definitionsSortedAndFiltered[$sortedClass] = $definitions[$sortedClass];
        }
        $definitions = $definitionsSortedAndFiltered;
        unset($definitionsSortedAndFiltered);
        
        // Rewrite the handler list based on the given overrides
        $aliasMap = [];
        foreach ($definitions as $handlerKey => $definition) {
            if (empty($definition->overrides)) {
                continue;
            }
            
            // Merge all overridden configuration properties into the current config object
            foreach ($definition->overrides as $overrideTargetKey) {
                $oDef = $definitions[$overrideTargetKey];
                unset($definitions[$overrideTargetKey]);
                
                // Merge values
                $definition->allowOverride = $oDef->allowOverride === false ? false : $definition->allowOverride;
                foreach (['interfaces', 'locations', 'overrideLocations', 'overrides', 'before', 'after'] as $key) {
                    $definition->$key = array_unique(array_merge($oDef->$key, $definition->$key));
                };
                
                // Create an alias
                $aliasMap[$oDef->className] = $definition->className;
                
                // Rewrite potential old aliases
                if (in_array($oDef->className, $aliasMap, true)) {
                    $aliasMap = array_map(static function ($className) use ($oDef, $definition): string {
                        return $className === $oDef->className ? $definition->className : $className;
                    }, $aliasMap);
                }
            }
        }
        
        // Rewrite dependencies
        foreach ($definitions as $definition) {
            foreach (['before', 'after'] as $listName) {
                foreach ($definition->$listName as $k => $handlerName) {
                    if (! isset($aliasMap[$handlerName])) {
                        continue;
                    }
                    $definition->$listName[$k] = $aliasMap[$handlerName];
                }
                $definition->$listName = array_unique($definition->$listName);
            }
        }
        
        return $definitions;
    }
    
    /**
     * Uses the "before" and "after" properties to sort the handlers based on the given dependencies
     *
     * @param   \Neunerlei\Configuration\Handler\HandlerDefinition[]  $definitions
     *
     * @return array
     */
    protected function sortDefinitions(array $definitions): array
    {
        // Build a list of the desired order
        $sorter = new IntuitiveTopSorter(array_keys($definitions));
        foreach ($definitions as $id => $definition) {
            foreach ($definition->after as $otherId) {
                $sorter->moveItemAfter($id, $otherId);
            }
            foreach ($definition->before as $otherId) {
                $sorter->moveItemBefore($id, $otherId);
            }
        }
        
        // Sort the list based on the dependencies
        $sortedDefinitions = [];
        foreach ($sorter->sort() as $id) {
            $sortedDefinitions[$id] = $definitions[$id];
        }
        
        return $sortedDefinitions;
    }
}
