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
 * Last modified: 2020.07.15 at 23:13
 */

declare(strict_types=1);


namespace Neunerlei\ConfigTests\Functional;


use Neunerlei\ConfigExample\ExampleCacheImplementation;
use Neunerlei\ConfigExample\Plugins\plugin1\Handler\PluginHandler\ExamplePluginHandler;
use Neunerlei\ConfigExample\Plugins\plugin2\Config\PluginRuntimeConfig;
use Neunerlei\ConfigExample\Project\Config\PluginConfig;
use Neunerlei\ConfigExample\Project\Handler\ContentElement\ExampleContentElementHandler;
use Neunerlei\ConfigExample\Project\Handler\NoopHandler\ExampleNoopHandler;
use Neunerlei\ConfigExample\Project\Handler\RuntimeHandler\ExampleRuntimeHandler;
use Neunerlei\ConfigTests\Fixture\EventFunctionalTest\FixtureTestEventDispatcher;
use Neunerlei\ConfigTests\LoaderTestTrait;
use Neunerlei\Configuration\Event\AfterConfigLoadEvent;
use Neunerlei\Configuration\Event\BeforeConfigLoadEvent;
use Neunerlei\Configuration\Event\ConfigDefinitionFilterEvent;
use Neunerlei\Configuration\Event\ConfigFinderFilterEvent;
use Neunerlei\Configuration\Event\ConfigHandlerFilterEvent;
use Neunerlei\Configuration\Event\HandlerFinderFilterEvent;
use Neunerlei\Configuration\Finder\ConfigFinder;
use Neunerlei\Configuration\Finder\FilteredHandlerFinder;
use Neunerlei\Configuration\Finder\HandlerFinder;
use Neunerlei\Configuration\Handler\ConfigHandlerInterface;
use Neunerlei\Configuration\Handler\HandlerDefinition;
use Neunerlei\Configuration\Loader\ConfigContext;
use Neunerlei\Configuration\Loader\Loader;
use Neunerlei\Configuration\Loader\LoaderContext;
use Neunerlei\Configuration\State\ConfigState;
use PHPUnit\Framework\TestCase;

class EventFunctionalTest extends TestCase
{
    use LoaderTestTrait;
    
    public function provideTestEventDispatchingData(): array
    {
        $cache = new ExampleCacheImplementation(false);
        
        return [
            // Normal event handling on the first load
            [
                false,
                function (FixtureTestEventDispatcher $dispatcher) use ($cache) {
                    $configDefinitionFilterEventAssertionsExecuted = false;
                    
                    $dispatcher->registerOnUnknownEventHandler(function (object $event) {
                        $this->fail('A unknown event was dispatched: ' . get_class($event));
                    });
                    
                    $dispatcher->registerHandler(AfterConfigLoadEvent::class,
                        function (AfterConfigLoadEvent $event) use (&$configDefinitionFilterEventAssertionsExecuted) {
                            if (! $configDefinitionFilterEventAssertionsExecuted) {
                                $this->fail('The ' . ConfigDefinitionFilterEvent::class . ' was not tested correctly!');
                            }
                            
                            $this->assertInstanceOf(LoaderContext::class, $event->getLoaderContext());
                            
                            $this->assertFalse($event->isCached());
                            $this->assertFalse($event->isRuntime());
                            $this->assertInstanceOf(ConfigState::class, $event->getState());
                            $this->assertEquals(['plugin1', 'contentElements', 'runtimeInstances'],
                                array_keys($event->getState()->getAll()));
                        });
                    
                    $dispatcher->registerHandler(BeforeConfigLoadEvent::class,
                        function (BeforeConfigLoadEvent $event) use ($cache) {
                            $this->assertFalse($event->isRuntime());
                            $this->assertInstanceOf(LoaderContext::class, $event->getLoaderContext());
                            $this->assertInstanceOf(Loader::class, $event->getLoader());
                            
                            // Register the cache handler
                            $event->getLoader()->setCache($cache);
                            
                            // Test context update
                            $context    = $event->getLoaderContext();
                            $tmpContext = new LoaderContext();
                            $event->setLoaderContext($tmpContext);
                            $this->assertSame($tmpContext, $event->getLoaderContext());
                            
                            $event->setLoaderContext($context);
                        });
                    
                    $dispatcher->registerHandler(ConfigDefinitionFilterEvent::class,
                        function (ConfigDefinitionFilterEvent $event) use (
                            &$configDefinitionFilterEventAssertionsExecuted
                        ) {
                            $this->assertInstanceOf(ConfigHandlerInterface::class,
                                $event->getHandlerDefinition()->handler);
                            $this->assertInstanceOf(ConfigContext::class, $event->getConfigContext());
                            $this->assertContains($event->getHandlerDefinition()->className, [
                                ExamplePluginHandler::class,
                                ExampleNoopHandler::class,
                                ExampleContentElementHandler::class,
                                ExampleRuntimeHandler::class,
                            ]);
                            
                            // We only test the methods for a single handler to avoid overhead
                            if ($event->getHandlerDefinition()->className === ExamplePluginHandler::class) {
                                $configDefinitionFilterEventAssertionsExecuted = true;
                                $this->assertEquals([
                                    PluginRuntimeConfig::class,
                                    PluginConfig::class,
                                ], $event->getConfigClasses());
                                
                                $configClasses = $event->getConfigClasses();
                                $event->setConfigClasses([]);
                                $this->assertEquals([], $event->getConfigClasses());
                                $event->setConfigClasses($configClasses);
                                
                                $this->assertEquals([], $event->getOverrideConfigClasses());
                                
                                $overrideClasses = $event->getOverrideConfigClasses();
                                $event->setOverrideConfigClasses([]);
                                $this->assertEquals([], $event->getOverrideConfigClasses());
                                $event->setOverrideConfigClasses($overrideClasses);
                                
                                $this->assertEquals([
                                    PluginRuntimeConfig::class => 'Plugin2',
                                    PluginConfig::class        => 'project',
                                ], $event->getClassNamespaceMap());
                                
                                $nsMap = $event->getClassNamespaceMap();
                                $event->setClassNamespaceMap([]);
                                $this->assertEquals([], $event->getClassNamespaceMap());
                                $event->setClassNamespaceMap($nsMap);
                                
                                $definition    = $event->getHandlerDefinition();
                                $tmpDefinition = new HandlerDefinition();
                                $event->setHandlerDefinition($tmpDefinition);
                                $this->assertSame($tmpDefinition, $event->getHandlerDefinition());
                                
                                $event->setHandlerDefinition($definition);
                            }
                        });
                    
                    $dispatcher->registerHandler(ConfigFinderFilterEvent::class,
                        function (ConfigFinderFilterEvent $event) {
                            $this->assertInstanceOf(ConfigFinder::class, $event->getConfigFinder());
                            $this->assertInstanceOf(LoaderContext::class, $event->getLoaderContext());
                            
                            $finder    = $event->getConfigFinder();
                            $tmpFinder = new class extends ConfigFinder
                            {
                            };
                            $event->setConfigFinder($tmpFinder);
                            $this->assertSame($tmpFinder, $event->getConfigFinder());
                            
                            $event->setConfigFinder($finder);
                            
                        });
                    
                    $dispatcher->registerHandler(ConfigHandlerFilterEvent::class,
                        function (ConfigHandlerFilterEvent $event) {
                            $this->assertInstanceOf(LoaderContext::class, $event->getLoaderContext());
                            $this->assertContainsOnlyInstancesOf(HandlerDefinition::class, $event->getHandlers());
                            $this->assertEquals([
                                ExamplePluginHandler::class,
                                ExampleContentElementHandler::class,
                                ExampleNoopHandler::class,
                                ExampleRuntimeHandler::class,
                            ], array_keys($event->getHandlers()));
                            
                            $handlers = $event->getHandlers();
                            $event->setHandlers([]);
                            $this->assertEquals([], $event->getHandlers());
                            
                            $event->setHandlers($handlers);
                        });
                    
                    $dispatcher->registerHandler(HandlerFinderFilterEvent::class,
                        function (HandlerFinderFilterEvent $event) {
                            $this->assertInstanceOf(HandlerFinder::class, $event->getHandlerFinder());
                            $this->assertInstanceOf(LoaderContext::class, $event->getLoaderContext());
                            
                            $finder    = $event->getHandlerFinder();
                            $tmpFinder = new FilteredHandlerFinder([], []);
                            $event->setHandlerFinder($tmpFinder);
                            $this->assertSame($tmpFinder, $event->getHandlerFinder());
                            
                            $event->setHandlerFinder($finder);
                            
                        });
                },
            ],
            // Check if events have the right state if executed with caching
            [
                false,
                function (FixtureTestEventDispatcher $dispatcher) use ($cache) {
                    $dispatcher->registerOnUnknownEventHandler(function () {
                        // Silence
                    });
                    
                    $dispatcher->registerHandler(BeforeConfigLoadEvent::class,
                        function (BeforeConfigLoadEvent $event) use ($cache) {
                            $this->assertFalse($event->isRuntime());
                            $event->getLoader()->setCache($cache);
                        });
                    
                    $dispatcher->registerHandler(AfterConfigLoadEvent::class,
                        function (AfterConfigLoadEvent $event) {
                            // This is true now, because the cache has the result from the last test run stored
                            $this->assertTrue($event->isCached());
                            $this->assertFalse($event->isRuntime());
                            
                            $this->assertInstanceOf(ConfigState::class, $event->getState());
                            $this->assertEquals(['plugin1', 'contentElements', 'runtimeInstances'],
                                array_keys($event->getState()->getAll()));
                        });
                },
            ],
            // Check if the runtime flag is set correctly
            [
                true,
                function (FixtureTestEventDispatcher $dispatcher) use ($cache) {
                    $dispatcher->registerHandler(BeforeConfigLoadEvent::class,
                        function (BeforeConfigLoadEvent $event) use ($cache) {
                            $this->assertTrue($event->isRuntime());
                            $event->getLoader()->setCache($cache);
                        });
                    
                    $dispatcher->registerHandler(AfterConfigLoadEvent::class,
                        function (AfterConfigLoadEvent $event) {
                            $this->assertFalse($event->isCached());
                            $this->assertTrue($event->isRuntime());
                            
                            $this->assertInstanceOf(ConfigState::class, $event->getState());
                            $this->assertEquals(['plugin1', 'contentElements', 'runtimeInstances'],
                                array_keys($event->getState()->getAll()));
                        });
                },
            ],
            
            // Check if the runtime flag is set, as well as the cache flag
            [
                true,
                function (FixtureTestEventDispatcher $dispatcher) use ($cache) {
                    $dispatcher->registerHandler(BeforeConfigLoadEvent::class,
                        function (BeforeConfigLoadEvent $event) use ($cache) {
                            $this->assertTrue($event->isRuntime());
                            $event->getLoader()->setCache($cache);
                        });
                    
                    $dispatcher->registerHandler(AfterConfigLoadEvent::class,
                        function (AfterConfigLoadEvent $event) {
                            $this->assertTrue($event->isCached());
                            $this->assertTrue($event->isRuntime());
                            
                            $this->assertInstanceOf(ConfigState::class, $event->getState());
                            $this->assertEquals(['plugin1', 'contentElements', 'runtimeInstances'],
                                array_keys($event->getState()->getAll()));
                        });
                },
            ],
        ];
    }
    
    /**
     * @param   bool      $isRuntime
     * @param   callable  $dispatcherConfigurator
     *
     * @dataProvider provideTestEventDispatchingData
     */
    public function testEventDispatching(bool $isRuntime, callable $dispatcherConfigurator): void
    {
        $loader = $this->makeConfiguredLoaderInstance([], ['Handler']);
        $this->registerExampleRootLocations($loader);
        $dispatcher = new FixtureTestEventDispatcher();
        $dispatcherConfigurator($dispatcher);
        $loader->setEventDispatcher($dispatcher);
        $loader->load($isRuntime);
        $this->assertTrue($dispatcher->haveAllHandlersBeenTriggered(), 'Not all handlers have been triggered!');
    }
}
