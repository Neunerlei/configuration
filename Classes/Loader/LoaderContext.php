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
 * Last modified: 2020.07.06 at 18:41
 */

declare(strict_types=1);


namespace Neunerlei\Configuration\Loader;


use AppendIterator;
use Neunerlei\Configuration\Finder\HandlerFinderInterface;
use Neunerlei\Configuration\Handler\ConfigHandlerInterface;
use Neunerlei\Configuration\Util\ConfigContextAwareInterface;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\SimpleCache\CacheInterface;

class LoaderContext
{

    /**
     * The implementation that finds handler classes in the registered locations
     *
     * @var HandlerFinderInterface
     */
    public $handlerFinder;

    /**
     * The implementation that finds the config classes in the registered locations
     *
     * @var \Neunerlei\Configuration\Finder\ConfigFinderInterface
     */
    public $configFinder;

    /**
     * A list of filesystem iterators that act as root locations.
     * This is used when the configuration of plugins/extension should be loaded
     * in addition to a project configuration.
     *
     * @var \Neunerlei\Configuration\Loader\NamespaceAwareFilesystemIterator[]|AppendIterator
     */
    public $rootLocations;

    /**
     * A list of handler locations, relative to the root locations
     * This list contains either iterators or strings
     *
     * @var \FilesystemIterator[]|string[]
     */
    public $handlerLocations = [];

    /**
     * A list of registered handler instances
     *
     * @var ConfigHandlerInterface[]
     */
    public $handlers = [];

    /**
     * The list of config modifiers to apply when a stack is processed
     *
     * @var \Neunerlei\Configuration\Modifier\ConfigModifierInterface[]
     */
    public $modifiers = [];

    /**
     * Optional cache instance to store the compiled configuration state
     *
     * @var CacheInterface|null
     */
    public $cache;

    /**
     * Contains the options to use when merging a cached state into an existing, initial state object.
     *
     * @var array
     * @see \Neunerlei\Configuration\State\ArraysWatchable::mergeStates()
     */
    public $cacheMergeOptions = [];

    /**
     * Optional psr-11 container implementation to create instances with
     *
     * @var ContainerInterface|null
     */
    public $container;

    /**
     * Optional PSR-14 event dispatcher implementation
     *
     * @var \Psr\EventDispatcher\EventDispatcherInterface|null
     */
    public $eventDispatcher;

    /**
     * A unique type key for this configuration, so we don't
     * create an overlap when different configurations are loaded
     *
     * @var string
     */
    public $type;

    /**
     * Something like "dev"/"prod"/"stage" or similar to
     * describe your current environment.
     *
     * @var string
     */
    public $environment;

    /**
     * The name of the class to use as context.
     *
     * @var string|null
     */
    public $configContextClass = ConfigContext::class;

    /**
     * Holds the instance of the config context after it was created
     *
     * @var \Neunerlei\Configuration\Loader\ConfigContext|null
     */
    public $configContext;

    /**
     * Helper to create a new instance of a class.
     * It will try to get the class from the registered container or use $creationFallback()
     * to create the instance itself if either there is no container, or the container
     * does not know how to create the required instance
     *
     * @param   string         $classname         The name of the class to instantiate
     * @param   callable|null  $creationFallback  Executed if no container is given, or the container
     *                                            does not know how to instantiate the class
     *
     * @return mixed
     */
    public function getInstance(string $classname, ?callable $creationFallback = null)
    {
        if (isset($this->container) && $this->container->has($classname)) {
            $i = $this->container->get($classname);
        } else {
            if ($creationFallback === null) {
                $creationFallback = static function ($classname) { return new $classname(); };
            }
            $i = $creationFallback($classname);
        }

        // Inject the context if required
        if (isset($this->configContext) && $i instanceof ConfigContextAwareInterface) {
            $i->setConfigContext($this->configContext);
        }

        return $i;
    }

    /**
     * Helper to dispatch a PSR-14 event if one was registered in the loader.
     * If no event handler is registered, the execution will be ignored
     *
     * @param   object  $event  The event to dispatch
     */
    public function dispatchEvent(object $event): void
    {
        if ($this->eventDispatcher instanceof EventDispatcherInterface) {
            $this->eventDispatcher->dispatch($event);
        }
    }
}
