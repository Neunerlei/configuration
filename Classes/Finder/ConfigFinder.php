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
 * Last modified: 2020.07.06 at 21:55
 */

declare(strict_types=1);


namespace Neunerlei\Configuration\Finder;


use Laminas\File\ClassFileLocator;
use Neunerlei\Configuration\Event\ConfigDefinitionFilterEvent;
use Neunerlei\Configuration\Exception\ConfigClassNotAutoloadableException;
use Neunerlei\Configuration\Handler\HandlerDefinition;
use Neunerlei\Configuration\Loader\ConfigContext;
use Neunerlei\Configuration\Loader\ConfigDefinition;
use Neunerlei\Configuration\Loader\NamespaceAwareSplFileInfo;
use Neunerlei\Configuration\Modifier\ModifierContext;
use Neunerlei\Configuration\Util\LocationIteratorTrait;
use Neunerlei\PathUtil\Path;
use SplFileInfo;

class ConfigFinder implements ConfigFinderInterface
{
    use LocationIteratorTrait;

    /**
     * @inheritDoc
     */
    public function find(HandlerDefinition $handlerDefinition, ConfigContext $configContext): ConfigDefinition
    {
        // Find the class lists
        [$classes, $overrideClasses, $classNamespaceMap] = $this->findClasses($handlerDefinition, $configContext);

        // Apply the modifiers
        [$classes, $classNamespaceMap] = $this->applyModifiers(
            $handlerDefinition,
            $configContext,
            $classes,
            $overrideClasses,
            $classNamespaceMap
        );

        // Allow filtering
        $configContext->getLoaderContext()->dispatchEvent(($e = new ConfigDefinitionFilterEvent(
            $handlerDefinition,
            $configContext,
            $classes,
            $overrideClasses,
            $classNamespaceMap
        )));
        $handlerDefinition = $e->getHandlerDefinition();
        $classes           = $e->getConfigClasses();
        $overrideClasses   = $e->getOverrideConfigClasses();
        $classNamespaceMap = $e->getClassNamespaceMap();

        // Make a new stack
        return new ConfigDefinition(
            $handlerDefinition,
            $configContext,
            $classes,
            $overrideClasses,
            $classNamespaceMap
        );
    }

    /**
     * Iterates all locations in a given handler definition in order to find the list of config classes.
     *
     * Returns an array containing three child arrays.
     * [0] The ordered list of all classes
     * [1] The ordered list of registered override classes (already merged into [0])
     * [2] A map between a class name and the matching namespace
     *
     * @param   \Neunerlei\Configuration\Handler\HandlerDefinition  $handlerDefinition
     * @param   \Neunerlei\Configuration\Loader\ConfigContext       $configContext
     *
     * @return array
     */
    protected function findClasses(HandlerDefinition $handlerDefinition, ConfigContext $configContext): array
    {
        $classes           = [array_keys($handlerDefinition->defaultConfigClasses)];
        $overrideClasses   = [];
        $classNamespaceMap = $handlerDefinition->defaultConfigClasses;
        foreach ($handlerDefinition->locations as $location) {
            // Prepare override declaration
            $overrides = false;
            if ($handlerDefinition->allowOverride) {
                $overrides = $handlerDefinition->overrideLocations;
                if (empty($overrides)) {
                    $overrides[] = 'Override';
                }
            };
            // Iterate the root locations
            $scannedDirectories = [];
            foreach ($configContext->getLoaderContext()->rootLocations as $rootLocation) {
                /** @var \Neunerlei\Configuration\Loader\NamespaceAwareSplFileInfo $rootLocation */

                // Load the list of classes at the location
                $rootPath = $rootLocation->getPathname();
                $path     = Path::join($rootPath, $location);
                foreach ($this->prepareLocationIterator($path) as $preparedLocation) {
                    $classes = $this->getClassesInLocation(
                        $rootLocation,
                        $preparedLocation,
                        $scannedDirectories,
                        $classNamespaceMap,
                        $classes);
                }

                // Check if we have to find overrides
                if ($overrides !== false) {
                    foreach ($overrides as $override) {
                        $overridePath = Path::join($path, $override);
                        foreach ($this->prepareLocationIterator($overridePath) as $preparedLocation) {
                            $overrideClasses = $this->getClassesInLocation(
                                $rootLocation,
                                $preparedLocation,
                                $scannedDirectories,
                                $classNamespaceMap,
                                $overrideClasses);
                        }
                    }
                }
            }
        }

        // Merge all classes into a single list
        $classes         = array_merge([], ...$classes);
        $overrideClasses = array_merge([], ...$overrideClasses);

        // Remove all classes that are not in our interface list
        $filter = static function (string $className) use ($handlerDefinition) {
            // Check if the class can be loaded
            if (! class_exists($className)) {
                // Ignore interfaces and traits
                if (interface_exists($className) || trait_exists($className)) {
                    return false;
                }

                throw new ConfigClassNotAutoloadableException(
                    'The configuration class "' . $className . '" is auto-loadable!');
            }

            return ! empty(array_intersect(
                class_implements($className),
                $handlerDefinition->interfaces
            ));
        };

        $classes         = array_filter($classes, $filter);
        $overrideClasses = array_filter($overrideClasses, $filter);

        // Clean up the namespace map
        $classNamespaceMap = array_filter($classNamespaceMap, static function (string $classname) use ($classes) {
            return in_array($classname, $classes, true);
        }, ARRAY_FILTER_USE_KEY);

        // Remove all overrides rom the main class list
        $classes = array_diff($classes, $overrideClasses);

        // Merge both lists and make sure the classes are unique
        $classes = array_unique(array_merge($classes, $overrideClasses));

        // Build output
        return [$classes, $overrideClasses, $classNamespaceMap];
    }

    /**
     * Partial to collect the classes inside a prepared location
     *
     * @param   \Neunerlei\Configuration\Loader\NamespaceAwareSplFileInfo  $rootLocation
     * @param   \SplFileInfo                                               $fileInfo
     * @param   array                                                      $scannedDirectories
     * @param   array                                                      $classNamespaceMap
     * @param   array                                                      $classes
     *
     * @return array
     * @internal
     */
    protected function getClassesInLocation(
        NamespaceAwareSplFileInfo $rootLocation,
        SplFileInfo $fileInfo,
        array &$scannedDirectories,
        array &$classNamespaceMap,
        array $classes
    ): array {
        $directory = $fileInfo->isDir() ? $fileInfo->getPathname() : $fileInfo->getPath();
        if (! in_array($directory, $scannedDirectories, true)) {
            $scannedDirectories[] = $directory;
            foreach (new ClassFileLocator($directory) as $phpFile) {
                /** @var \Laminas\File\PhpClassFile $phpFile */
                $classes[] = $phpFile->getClasses();

                // Generate the namespace map
                foreach ($phpFile->getClasses() as $class) {
                    // Wrap the namespace generator in order to incorporate the root location
                    $phpFileInfo               = new NamespaceAwareSplFileInfo($phpFile->getPathname(),
                        function (SplFileInfo $fileInfo, string $className) use ($rootLocation): string {
                            $namespaceOrGenerator = $rootLocation->getNamespaceOrGenerator();
                            if (is_string($namespaceOrGenerator)) {
                                return $namespaceOrGenerator;
                            }

                            return ($namespaceOrGenerator)($rootLocation, $className, $fileInfo);
                        });
                    $classNamespaceMap[$class] = $phpFileInfo->getNamespace($class);
                }
            }
        }

        return $classes;
    }

    /**
     * Applies the registered modifiers to the prepared data before the handler is executed
     *
     * @param   \Neunerlei\Configuration\Handler\HandlerDefinition  $handlerDefinition
     * @param   \Neunerlei\Configuration\Loader\ConfigContext       $context
     * @param   array                                               $configClasses
     * @param   array                                               $overrideConfigClasses
     * @param   array                                               $classNamespaceMap
     *
     * @return array
     */
    protected function applyModifiers(
        HandlerDefinition $handlerDefinition,
        ConfigContext $context,
        array $configClasses,
        array $overrideConfigClasses,
        array $classNamespaceMap
    ): array {
        // Prepare the context
        $modifierContext = new ModifierContext(
            $handlerDefinition,
            $context,
            $configClasses,
            $overrideConfigClasses,
            $classNamespaceMap);

        // Apply the modifiers
        foreach ($context->getLoaderContext()->modifiers as $modifier) {
            $modifier->apply($modifierContext);
        }

        // Update the local storage
        return [$modifierContext->getConfigClasses(), $modifierContext->getClassNamespaceMap()];
    }
}
