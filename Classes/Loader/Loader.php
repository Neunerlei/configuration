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
 * Last modified: 2020.07.05 at 10:48
 */

declare(strict_types=1);


namespace Neunerlei\Configuration\Loader;


use AppendIterator;
use Neunerlei\Arrays\Arrays;
use Neunerlei\Configuration\Event\AfterConfigLoadEvent;
use Neunerlei\Configuration\Event\BeforeConfigLoadEvent;
use Neunerlei\Configuration\Event\BeforeStateCachingEvent;
use Neunerlei\Configuration\Event\ConfigFinderFilterEvent;
use Neunerlei\Configuration\Event\HandlerFinderFilterEvent;
use Neunerlei\Configuration\Exception\InvalidContextClassException;
use Neunerlei\Configuration\Finder\ConfigFinder;
use Neunerlei\Configuration\Finder\ConfigFinderInterface;
use Neunerlei\Configuration\Finder\HandlerFinder;
use Neunerlei\Configuration\Finder\HandlerFinderInterface;
use Neunerlei\Configuration\Handler\ConfigHandlerInterface;
use Neunerlei\Configuration\Modifier\Builtin\Order\ConfigOrderModifier;
use Neunerlei\Configuration\Modifier\Builtin\Replace\ConfigReplaceModifier;
use Neunerlei\Configuration\Modifier\ConfigModifierInterface;
use Neunerlei\Configuration\State\ConfigState;
use Neunerlei\Configuration\Util\LocationIteratorTrait;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\SimpleCache\CacheInterface;
use SplFileInfo;

class Loader
{
    use LocationIteratorTrait;

    /**
     * The context for the loader instance itself
     *
     * @var \Neunerlei\Configuration\Loader\LoaderContext
     */
    protected $loaderContext;

    /**
     * Loader constructor.
     *
     * @param   string  $type               A unique type key for this configuration, so we don't
     *                                      create an overlap when different configurations are loaded
     * @param   string  $environment        Something like "dev"/"prod"/"stage" or similar to
     *                                      describe your current environment.
     */
    public function __construct(string $type, string $environment)
    {
        $this->loaderContext                = new LoaderContext();
        $this->loaderContext->type          = $type;
        $this->loaderContext->environment   = $environment;
        $this->loaderContext->rootLocations = new AppendIterator();
        $this->clearModifiers();
    }

    /**
     * Returns the currently set config type
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->loaderContext->type;
    }

    /**
     * Allows you to override the config type
     *
     * @param   string  $type  A unique type key for this configuration, so we don't
     *                         create an overlap when different configurations are loaded
     *
     * @return $this
     */
    public function setType(string $type): self
    {
        $this->loaderContext->type = $type;

        return $this;
    }

    /**
     * Returns the currently set environment
     *
     * @return string
     */
    public function getEnvironment(): string
    {
        return $this->loaderContext->environment;
    }

    /**
     * Allows you to override the config environment
     *
     * @param   string  $environment  Something like "dev"/"prod"/"stage" or similar to
     *                                describe your current environment.
     *
     * @return $this
     */
    public function setEnvironment(string $environment): self
    {
        $this->loaderContext->environment = $environment;

        return $this;
    }

    /**
     * Registers a new root location. A root location be one of the following:
     * - An absolute path to a directory
     * - A FilesystemIterator containing multiple directories
     * - A glob pattern to find a single or multiple directories.
     *
     * This allows you to find configuration directories for plugin-systems with minimal effort.
     *
     * A namespace is used to allow the registered handlers to perform "plugin" based configuration
     * options. Therefore every location has to be tied to a namespace, which will be set in the ConfigContext
     * when a single configuration class is processed. You can provide a namespace using one of the options:
     * - A string to that is used for all registered root locations
     * - A callback which is called once per found class. It recevies the following parameters
     *     0: The SplFileInfo object of the ROOT LOCATION
     *     1: The fully qualified name of the class
     *     2: The SplFileInfo object of the class file itself
     * parameter. With that you can adjust the namespace generated for each class.
     * - Leave the parameter empty to automatically generate a namespace based on the directory name
     *
     * @param   string|\FilesystemIterator  $globPatternOrIterator  The location(s) you want to register
     * @param   string|callable|null        $namespaceOrGenerator   The namespace or namespace generator as a callable
     *
     *
     * @return $this
     */
    public function registerRootLocation($globPatternOrIterator, $namespaceOrGenerator = null): self
    {
        $this->loaderContext->rootLocations->append(
            new NamespaceAwareFilesystemIterator(
                $this->prepareLocationIterator($globPatternOrIterator),
                $namespaceOrGenerator ?? static function (SplFileInfo $fileInfo) {
                    return 'namespace-' . $fileInfo->getPathname();
                }
            )
        );

        return $this;
    }

    /**
     * Removes all registered root locations
     *
     * @return $this
     */
    public function clearRootLocations(): self
    {
        $this->loaderContext->rootLocations = new AppendIterator();

        return $this;
    }

    /**
     * Allows you to provide a location to find handler classes at.
     * You have multiple options you can pass to this method:
     * - An absolute path as a string to a directory where handler classes are located
     * - An absolute glob pattern to find multiple directories where handler classes are located
     * - A FilesystemIterator containing handler locations
     * - A relative glob pattern or path to be resolved relative to all registered rootLocations
     *
     * @param $globPatternOrIterator
     *
     * @return $this
     */
    public function registerHandlerLocation($globPatternOrIterator): self
    {
        $this->loaderContext->handlerLocations[] = $globPatternOrIterator;

        return $this;
    }

    /**
     * Removes all registered handler locations
     *
     * @return $this
     */
    public function clearHandlerLocations(): self
    {
        $this->loaderContext->handlerLocations = [];

        return $this;
    }

    /**
     * Allows you to register an already instantiated handler object which will be merged
     * into the list of search handlers found by registerHandlerLocation()
     *
     * @param   \Neunerlei\Configuration\Handler\ConfigHandlerInterface  $handler
     *
     * @return $this
     */
    public function registerHandler(ConfigHandlerInterface $handler): self
    {
        $this->loaderContext->handlers[] = $handler;

        return $this;
    }

    /**
     * Removes all registered handler instances
     *
     * @return $this
     */
    public function clearHandlers(): self
    {
        $this->loaderContext->handlers = [];

        return $this;
    }

    /**
     * Allows you to register a new modifier to, well modify, the list of configClasses
     * that will be passed to a handler.
     *
     * @param   \Neunerlei\Configuration\Modifier\ConfigModifierInterface  $modifier
     *
     * @return $this
     */
    public function registerModifier(ConfigModifierInterface $modifier): self
    {
        $this->loaderContext->modifiers[$modifier->getKey()] = $modifier;

        return $this;
    }

    /**
     * Resets the list of modifiers to the default
     *
     * @return $this
     */
    public function clearModifiers(): self
    {
        $this->loaderContext->modifiers = [
            ($m = new ConfigOrderModifier())->getKey()   => $m,
            ($m = new ConfigReplaceModifier())->getKey() => $m,
        ];

        return $this;
    }

    /**
     * Allows you to override the default config context class with another one of your liking.
     * IMPORTANT: The class you pass MUST extend the ConfigContext base class
     *
     * @param   string  $configContextClass  The class to use instead of the default context class
     *
     * @return $this
     * @throws \Neunerlei\Configuration\Exception\InvalidContextClassException
     */
    public function setConfigContextClass(string $configContextClass): self
    {
        if (! class_exists($configContextClass)) {
            throw new InvalidContextClassException(
                'The given context class ' . $configContextClass . ' does not exist!');
        }
        if (! in_array(ConfigContext::class, class_parents($configContextClass), true)) {
            throw new InvalidContextClassException(
                'The given context class ' . $configContextClass . ' has to extend the ' .
                ConfigContext::class . ' base class!');
        }
        $this->loaderContext->configContextClass = $configContextClass;

        return $this;
    }

    /**
     * To increase performance you can pass a cache implementation the loader
     * uses to store the compiled configuration object. As long as the cache is valid
     * the stored state will be served by the "load()" method.
     *
     * You have to clear the cache in order for load() to recompile the configuration
     * from your source classes
     *
     * @param   \Psr\SimpleCache\CacheInterface  $cache
     *
     * @return $this
     */
    public function setCache(?CacheInterface $cache): self
    {
        $this->loaderContext->cache = $cache;

        return $this;
    }

    /**
     * Can be used to inject a PSR-11 compliant container
     *
     * @param   \Psr\Container\ContainerInterface|null  $container
     *
     * @return \Neunerlei\Configuration\Loader\Loader
     */
    public function setContainer(?ContainerInterface $container): self
    {
        $this->loaderContext->container = $container;

        return $this;
    }

    /**
     * Can be used to inject an optional PSR-14 event dispatcher
     *
     * @param   \Psr\EventDispatcher\EventDispatcherInterface|null  $eventDispatcher
     *
     * @return $this
     */
    public function setEventDispatcher(?EventDispatcherInterface $eventDispatcher): self
    {
        $this->loaderContext->eventDispatcher = $eventDispatcher;

        return $this;
    }

    /**
     * Allows you to register your own implementation of the class we use to find
     * and initialize the handler instances
     *
     * @param   \Neunerlei\Configuration\Finder\HandlerFinderInterface|null  $handlerFinder
     *
     * @return $this
     */
    public function setHandlerFinder(?HandlerFinderInterface $handlerFinder): self
    {
        $this->loaderContext->handlerFinder = $handlerFinder;

        return $this;
    }

    /**
     * Allows you to register your own implementation of the class we use to find
     * the configuration classes in your root locations
     *
     * @param   \Neunerlei\Configuration\Finder\ConfigFinderInterface|null  $configFinder
     *
     * @return $this
     */
    public function setConfigFinder(?ConfigFinderInterface $configFinder): self
    {
        $this->loaderContext->configFinder = $configFinder;

        return $this;
    }

    /**
     * Uses the previously given configuration to load the configuration
     * classes into a ConfigState object which then will be returned
     *
     * @param   bool  $asRuntime  By default the whole config state content will be cached (if a cache was registered).
     *                            If you set this to true only the found config sources will be cached, so you can also
     *                            set instances or closures into your state. With the downside that the config classes
     *                            have to be executed on each run at "runtime", hence the name.
     *
     * @return \Neunerlei\Configuration\State\ConfigState
     */
    public function load(bool $asRuntime = false): ConfigState
    {
        // Prepare the instances
        $state = new ConfigState([]);

        // Create a new reference on the loader context to avoid pollution
        $cleanContext                       = $this->loaderContext;
        $this->loaderContext                = clone $this->loaderContext;
        $this->loaderContext->configContext = $this->makeConfigContext($this->loaderContext, $state);
        $isCached                           = false;

        try {
            // Allow filtering
            $this->loaderContext->dispatchEvent(
                ($e = new BeforeConfigLoadEvent($asRuntime, $this->loaderContext, $this)));
            $this->loaderContext = $e->getLoaderContext();

            // Handle a runtime load
            if ($asRuntime) {
                $state = $this->performRuntimeLoad($isCached);
            } else {
                $state = $this->performLoad($isCached);
            }

            // Allow filtering
            $this->loaderContext->dispatchEvent(
                ($e = new AfterConfigLoadEvent($asRuntime, $isCached, $this->loaderContext, $state)));
            $state = $e->getState();
        } finally {
            // Revert the clean context
            $this->loaderContext = $cleanContext;
        }

        // Done
        return $state;
    }

    /**
     * Creates a new instance of the registered config context class and returns it
     *
     * @param   \Neunerlei\Configuration\Loader\LoaderContext  $loaderContext
     * @param   \Neunerlei\Configuration\State\ConfigState     $state
     *
     * @return \Neunerlei\Configuration\Loader\ConfigContext
     */
    protected function makeConfigContext(LoaderContext $loaderContext, ConfigState $state): ConfigContext
    {
        // Create the configContext
        /** @var \Neunerlei\Configuration\Loader\ConfigContext $configContext */
        $configContext = $loaderContext->getInstance($loaderContext->configContextClass);
        $configContext->initialize($loaderContext, $state);

        return $configContext;
    }

    /**
     * Returns the instance of the config finder implementation.
     * It is either retrieved from the globally registered instance or by creating a new instance
     * using the container.
     *
     * @param   \Neunerlei\Configuration\Loader\LoaderContext  $loaderContext
     *
     * @return \Neunerlei\Configuration\Finder\ConfigFinderInterface
     */
    protected function makeConfigFinder(LoaderContext $loaderContext): ConfigFinderInterface
    {
        if (! empty($this->loaderContext->configFinder)) {
            $finder = $this->loaderContext->configFinder;
        } else {
            $finder = $loaderContext
                ->getInstance(ConfigFinderInterface::class, static function () {
                    return new ConfigFinder();
                });
        }

        // Allow filtering
        $loaderContext->dispatchEvent(($e = new ConfigFinderFilterEvent($finder, $loaderContext)));

        return $e->getConfigFinder();

    }

    /**
     * Returns the instance of the handler finder implementation.
     * It is either retrieved from the globally registered instance or by creating a new instance
     * using the container.
     *
     * @param   \Neunerlei\Configuration\Loader\LoaderContext  $loaderContext
     *
     * @return \Neunerlei\Configuration\Finder\HandlerFinderInterface
     */
    protected function makeHandlerFinder(LoaderContext $loaderContext): HandlerFinderInterface
    {
        if (! empty($this->loaderContext->handlerFinder)) {
            $finder = $this->loaderContext->handlerFinder;
        } else {
            $finder = $loaderContext
                ->getInstance(HandlerFinderInterface::class, static function () {
                    return new HandlerFinder();
                });
        }

        // Allow filtering
        $loaderContext->dispatchEvent(($e = new HandlerFinderFilterEvent($finder, $loaderContext)));

        return $e->getHandlerFinder();
    }

    /**
     * Generates a cached key based on the current configuration
     *
     * @param   \Neunerlei\Configuration\Loader\LoaderContext  $loaderContext
     * @param   bool                                           $isRuntime
     *
     * @return string
     */
    protected function makeCacheKey(LoaderContext $loaderContext, bool $isRuntime): string
    {
        return 'configuration-' . $loaderContext->type . '-' .
               $loaderContext->environment .
               ($isRuntime ? '-runtimeDefinitions' : '');
    }

    /**
     * Performs a runtime load where the config sources are cached and executed on every run.
     * This can be used for creating instances for your configuration.
     *
     * @param   bool  $isCached
     *
     * @return \Neunerlei\Configuration\State\ConfigState
     * @throws \JsonException
     */
    protected function performRuntimeLoad(bool &$isCached): ConfigState
    {
        // Prepare cache storage
        $hasCache = isset($this->loaderContext->cache);
        $cacheKey = $this->makeCacheKey($this->loaderContext, true);

        // Load the config definitions
        /** @var \Neunerlei\Configuration\Loader\ConfigDefinition[] $configDefinitions */
        $configDefinitions = [];
        if ($hasCache && $this->loaderContext->cache->has($cacheKey)) {
            $isCached = true;

            // Rehydrate the cached definitions
            foreach (
                Arrays::makeFromJson($this->loaderContext->cache->get($cacheKey))
                as $dryConfigDefinition
            ) {
                $configDefinitions[] = ConfigDefinition::hydrate($this->loaderContext, $dryConfigDefinition);
            }
        } else {
            // Find the config definitions
            $handlerFinder = $this->makeHandlerFinder($this->loaderContext);
            $configFinder  = $this->makeConfigFinder($this->loaderContext);
            foreach ($handlerFinder->find($this->loaderContext) as $handlerDefinition) {
                $configDefinitions[] = $configFinder->find($handlerDefinition, $this->loaderContext->configContext);
            }

            // Cache the definitions if we have a cache to write them to
            if ($hasCache) {
                // Dehydrate the definitions
                $dryConfigDefinitions = [];
                foreach ($configDefinitions as $configDefinition) {
                    $dryConfigDefinitions[] = $configDefinition->dehydrate();
                }
                // Cache the definitions for the next run
                $this->loaderContext->cache->set($cacheKey, json_encode($dryConfigDefinitions, JSON_THROW_ON_ERROR));
            }
        }

        // Process the config definitions
        foreach ($configDefinitions as $configDefinition) {
            $configDefinition->process();
        }

        // Extract the state
        return $this->loaderContext->configContext->getState();
    }

    /**
     * Performs a normal config load where the whole state object gets cached (if a cache is available)
     * This saves the most performance but you can only fill the state with JSON-serializable data
     *
     * @param   bool  $isCached
     *
     * @return \Neunerlei\Configuration\State\ConfigState
     * @throws \JsonException
     */
    protected function performLoad(bool &$isCached): ConfigState
    {
        // Prepare cache storage
        $hasCache = isset($this->loaderContext->cache);
        $cacheKey = $this->makeCacheKey($this->loaderContext, false);

        // Handle a normal load
        if ($hasCache && $this->loaderContext->cache->has($cacheKey)) {
            $isCached = true;

            // Load the state from the cache
            return new ConfigState(Arrays::makeFromJson($this->loaderContext->cache->get($cacheKey)));
        }

        // Compile state from config files
        $handlerFinder = $this->makeHandlerFinder($this->loaderContext);
        $configFinder  = $this->makeConfigFinder($this->loaderContext);

        // Run the handlers
        foreach ($handlerFinder->find($this->loaderContext) as $handlerDefinition) {
            $configFinder->find($handlerDefinition, $this->loaderContext->configContext)->process();
        }

        // Allow filtering before we write the state into the cache
        if (isset($this->loaderContext->eventDispatcher)) {
            $this->loaderContext->eventDispatcher->dispatch(
                new BeforeStateCachingEvent($hasCache, $cacheKey, $this->loaderContext, $this)
            );
        }

        // Store the state into the cache
        if ($hasCache) {
            $this->loaderContext->cache->set($cacheKey, json_encode(
                $this->loaderContext->configContext->getState()->getAll(), JSON_THROW_ON_ERROR));
        }

        // Extract the state
        return $this->loaderContext->configContext->getState();
    }
}
