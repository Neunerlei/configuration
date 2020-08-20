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
 * Last modified: 2020.07.13 at 14:06
 */

declare(strict_types=1);


namespace Neunerlei\ConfigTests\Unit;


use GlobIterator;
use Neunerlei\ConfigExample\ExampleCacheImplementation;
use Neunerlei\ConfigTests\Fixture\FixtureContextAwareClass;
use Neunerlei\ConfigTests\Fixture\FixtureTestContext;
use Neunerlei\ConfigTests\LoaderTestTrait;
use Neunerlei\ConfigTests\TestHelperTrait;
use Neunerlei\Configuration\Event\ConfigFinderFilterEvent;
use Neunerlei\Configuration\Event\HandlerFinderFilterEvent;
use Neunerlei\Configuration\Exception\InvalidContextClassException;
use Neunerlei\Configuration\Finder\ConfigFinder;
use Neunerlei\Configuration\Finder\ConfigFinderInterface;
use Neunerlei\Configuration\Finder\HandlerFinder;
use Neunerlei\Configuration\Finder\HandlerFinderInterface;
use Neunerlei\Configuration\Handler\ConfigHandlerInterface;
use Neunerlei\Configuration\Loader\ConfigContext;
use Neunerlei\Configuration\Loader\NamespaceAwareSplFileInfo;
use Neunerlei\Configuration\Modifier\Builtin\Order\ConfigOrderModifier;
use Neunerlei\Configuration\Modifier\Builtin\Replace\ConfigReplaceModifier;
use Neunerlei\Configuration\Modifier\ConfigModifierInterface;
use Neunerlei\Configuration\State\ConfigState;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

class LoaderUnitTest extends TestCase
{
    use TestHelperTrait;
    use LoaderTestTrait;
    
    public function testEnvAndType(): void
    {
        $loader = $this->makeEmptyLoaderInstance();
        $this->assertEquals('testCase', $loader->getType());
        $loader->setType('fooType');
        $this->assertEquals('fooType', $loader->getType());
        
        $this->assertEquals('test', $loader->getEnvironment());
        $loader->setEnvironment('fooEnv');
        $this->assertEquals('fooEnv', $loader->getEnvironment());
        
        $context = $this->getLoaderContext($loader);
        $this->assertEquals('fooType', $context->type);
        $this->assertEquals('fooEnv', $context->environment);
    }
    
    public function testRootLocationsWithNamespaces(): void
    {
        $loader = $this->makeEmptyLoaderInstance();
        $this->registerExampleRootLocations($loader);
        $context = $this->getLoaderContext($loader);
        
        $actual = [];
        $this->assertEquals(5, iterator_count($context->rootLocations));
        $count = 0;
        foreach ($context->rootLocations as $location) {
            $this->assertInstanceOf(NamespaceAwareSplFileInfo::class, $location);
            // We have to set a class here, but as we use it in our generator, we can pass a foo value here
            $actual[$location->getPathname()] = $location->getNamespace((string)($count++));
        }
        
        $examplePath = $this->getExamplePath();
        $this->assertEquals([
            $examplePath . 'Plugins/plugin1' => 'Plugin1',
            $examplePath . 'Plugins/plugin2' => 'Plugin2',
            $examplePath . 'Plugins/plugin3' => 'Plugin3',
            $examplePath . 'Project'         => 'project',
            rtrim($examplePath, '/')         => 'namespace-' . rtrim($examplePath, '/'),
        
        ], $actual);
        
        $loader->clearRootLocations();
        $this->assertEquals(0, iterator_count($context->rootLocations));
    }
    
    public function testRegisterHandlerLocationOrHandler(): void
    {
        $loader      = $this->makeEmptyLoaderInstance();
        $handlerMock = $this->getMockBuilder(ConfigHandlerInterface::class)->getMockForAbstractClass();
        $loader->registerHandler($handlerMock);
        $loader->registerHandlerLocation('foo');
        $it = new GlobIterator($this->getExamplePath() . '*/Handler/**');
        $loader->registerHandlerLocation($it);
        $context = $this->getLoaderContext($loader);
        
        $this->assertEquals([$handlerMock], $context->handlers);
        
        $loader->clearHandlers();
        $this->assertEquals([], $context->handlers);
        
        $this->assertEquals(['foo', $it], $context->handlerLocations);
        
        $loader->clearHandlerLocations();
        $this->assertEquals([], $context->handlerLocations);
    }
    
    public function testSetContextClass(): void
    {
        $loader  = $this->makeEmptyLoaderInstance();
        $context = $this->getLoaderContext($loader);
        $this->assertEquals(ConfigContext::class, $context->configContextClass);
        $loader->setConfigContextClass(FixtureTestContext::class);
        $this->assertEquals(FixtureTestContext::class, $context->configContextClass);
    }
    
    public function provideTestSetContextClassFailuresData(): array
    {
        return [
            [FixtureContextAwareClass::class],
            [''],
        ];
    }
    
    /**
     * @param $class
     *
     * @dataProvider provideTestSetContextClassFailuresData
     */
    public function testSetContextClassFailures($class): void
    {
        $this->expectException(InvalidContextClassException::class);
        $loader = $this->makeEmptyLoaderInstance();
        $loader->setConfigContextClass($class);
    }
    
    public function provideTestSetDependencyInstanceData(): array
    {
        return [
            [
                'setCache',
                'cache',
                function () {
                    return new ExampleCacheImplementation();
                },
            ],
            [
                'setContainer',
                'container',
                function () {
                    return $this->getMockBuilder(ContainerInterface::class)->getMockForAbstractClass();
                },
            ],
            [
                'setEventDispatcher',
                'eventDispatcher',
                function () {
                    return $this->getMockBuilder(EventDispatcherInterface::class)->getMockForAbstractClass();
                },
            ],
            [
                'setHandlerFinder',
                'handlerFinder',
                function () {
                    return $this->getMockBuilder(HandlerFinderInterface::class)->getMockForAbstractClass();
                },
            ],
            [
                'setConfigFinder',
                'configFinder',
                function () {
                    return $this->getMockBuilder(ConfigFinderInterface::class)->getMockForAbstractClass();
                },
            ],
        ];
    }
    
    /**
     * @param   string    $setter
     * @param   string    $property
     * @param   callable  $creator
     *
     * @dataProvider provideTestSetDependencyInstanceData
     */
    public function testSetDependencyInstance(string $setter, string $property, callable $creator): void
    {
        $loader  = $this->makeEmptyLoaderInstance();
        $context = $this->getLoaderContext($loader);
        $this->assertNull($context->$property);
        
        $instance = $creator();
        $loader->$setter($instance);
        $this->assertSame($instance, $context->$property);
        
        $loader->$setter(null);
        $this->assertNull($context->$property);
    }
    
    public function testModifierRegistration(): void
    {
        $loader  = $this->makeEmptyLoaderInstance();
        $context = $this->getLoaderContext($loader);
        
        $this->assertEquals([
            ($m = new ConfigOrderModifier())->getKey()   => $m,
            ($m = new ConfigReplaceModifier())->getKey() => $m,
        ], $context->modifiers);
        
        $mock = $this->getMockBuilder(ConfigModifierInterface::class)
                     ->getMockForAbstractClass();
        $mock->method('getKey')->willReturn('mockModifier');
        $loader->registerModifier($mock);
        
        // Override the modifier
        $mock2 = $this->getMockBuilder(ConfigModifierInterface::class)
                      ->getMockForAbstractClass();
        $mock2->method('getKey')->willReturn('mockModifier');
        $loader->registerModifier($mock);
        
        $this->assertEquals([
            ($m = new ConfigOrderModifier())->getKey()   => $m,
            ($m = new ConfigReplaceModifier())->getKey() => $m,
            'mockModifier'                               => $mock2,
        ], $context->modifiers);
    }
    
    public function testMakeCacheKey(): void
    {
        $loader  = $this->makeEmptyLoaderInstance();
        $context = $this->getLoaderContext($loader);
        $caller  = $this->makeCaller($loader, 'makeCacheKey');
        $this->assertEquals('configuration-testCase-test', $caller($context, false));
        $this->assertEquals('configuration-testCase-test-runtimeDefinitions', $caller($context, true));
        
        $loader->setEnvironment('foo');
        $loader->setType('fooType');
        $this->assertEquals('configuration-fooType-foo', $caller($context, false));
        $this->assertEquals('configuration-fooType-foo-runtimeDefinitions', $caller($context, true));
    }
    
    public function testMakeConfigContext(): void
    {
        $loader                      = $this->makeEmptyLoaderInstance();
        $context                     = $this->getLoaderContext($loader);
        $context->configContextClass = FixtureTestContext::class;
        $caller                      = $this->makeCaller($loader, 'makeConfigContext');
        $state                       = new ConfigState([]);
        $configContext               = $caller($context, $state);
        
        $this->assertInstanceOf(FixtureTestContext::class, $configContext);
        $this->assertTrue($configContext->isInitialized);
    }
    
    public function provideTestFinderCreationData(): array
    {
        return [
            [
                'makeHandlerFinder',
                'handlerFinder',
                null,
                HandlerFinder::class,
                HandlerFinderFilterEvent::class,
            ],
            [
                'makeHandlerFinder',
                'handlerFinder',
                $mock = $this->getMockBuilder(HandlerFinderInterface::class)->getMockForAbstractClass(),
                get_class($mock),
                HandlerFinderFilterEvent::class,
            ],
            [
                'makeConfigFinder',
                'configFinder',
                null,
                ConfigFinder::class,
                ConfigFinderFilterEvent::class,
            ],
            [
                'makeConfigFinder',
                'configFinder',
                $mock = $this->getMockBuilder(ConfigFinderInterface::class)->getMockForAbstractClass(),
                get_class($mock),
                ConfigFinderFilterEvent::class,
            ],
        ];
    }
    
    /**
     * @param   string  $method
     * @param   string  $property
     * @param           $propertyVal
     * @param   string  $expectedInstanceClass
     * @param   string  $expectedEventClass
     *
     * @dataProvider provideTestFinderCreationData
     */
    public function testFinderCreation(
        string $method,
        string $property,
        $propertyVal,
        string $expectedInstanceClass,
        string $expectedEventClass
    ): void {
        $loader                   = $this->makeEmptyLoaderInstance();
        $context                  = $this->getLoaderContext($loader);
        $context->eventDispatcher = new class implements EventDispatcherInterface
        {
            public $dispatchedEvent;
            
            public function dispatch(object $event)
            {
                $this->dispatchedEvent = $event;
            }
        };
        
        $context->$property = $propertyVal;
        $caller             = $this->makeCaller($loader, $method);
        $instance           = $caller($context);
        $this->assertInstanceOf($expectedInstanceClass, $instance);
        $this->assertInstanceOf($expectedEventClass, $context->eventDispatcher->dispatchedEvent);
        
    }
    
}
