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
 * Last modified: 2020.07.14 at 18:50
 */

declare(strict_types=1);


namespace Neunerlei\ConfigTests\Unit;


use Neunerlei\ConfigTests\Fixture\ConfigHandlerAbstractTest\DefaultConfig\FixtureDefaultConfigHandler;
use Neunerlei\ConfigTests\Fixture\ConfigHandlerAbstractTest\DefaultConfig\FixtureDefaultConfigInterface;
use Neunerlei\ConfigTests\Fixture\FixtureContextAwareClass;
use Neunerlei\ConfigTests\Fixture\FixtureTestContext;
use Neunerlei\ConfigTests\Fixture\FixtureTestHandler;
use Neunerlei\ConfigTests\LoaderTestTrait;
use Neunerlei\ConfigTests\TestHelperTrait;
use Neunerlei\Configuration\Handler\AbstractConfigHandler;
use Neunerlei\Configuration\Handler\HandlerConfigurator;
use Neunerlei\Configuration\Handler\HandlerDefinition;
use Neunerlei\Configuration\Loader\ConfigContext;
use PHPUnit\Framework\TestCase;

class ConfigHandlerAbstractTest extends TestCase
{
    use TestHelperTrait;
    use LoaderTestTrait;

    public function testGetInstance()
    {
        $handler = $this->getMockBuilder(AbstractConfigHandler::class)->getMockForAbstractClass();
        $loader  = $this->makeConfiguredLoaderInstance();
        $handler->setConfigContext($this->getLoaderContext($loader)->configContext);

        // Test if "getInstance" creates a new instance
        $caller   = $this->makeCaller($handler, 'getInstance');
        $instance = $caller(FixtureContextAwareClass::class);
        self::assertInstanceOf(FixtureContextAwareClass::class, $instance);
        self::assertInstanceOf(ConfigContext::class, $instance->getContext());

        // Test if "getInstance" correctly calls the callback
        self::assertInstanceOf(FixtureTestContext::class, $caller(FixtureContextAwareClass::class, function () {
            return new FixtureTestContext();
        }));
    }


    public function testDefaultConfigClasses(): void
    {
        $loader = $this->makeConfiguredLoaderInstance(
            [$this->getFixturePath(__CLASS__) . 'DefaultConfig'],
            [(new FixtureDefaultConfigHandler())->useConfigClass()],
        );

        self::assertEquals([
            'keep'    => 'default',
            'default' => 'dummy2',
            'dummy1'  => ['dummy' => 1],
            'dummy2'  => ['dummy' => 2],
        ], $loader->load()->getAll());
    }

    public function testDefaultConfigState(): void
    {
        $loader = $this->makeConfiguredLoaderInstance(
            [$this->getFixturePath(__CLASS__) . 'DefaultConfig'],
            [(new FixtureDefaultConfigHandler())],
        );

        self::assertEquals([
            'my'      => 'key',
            'your'    => 'yourKey',
            'our'     => [
                'key' => [
                    'is' => 'key',
                ],
            ],
            'default' => 'dummy2',
            'dummy1'  => ['dummy' => 1],
            'dummy2'  => ['dummy' => 2],
        ], $loader->load()->getAll());
    }

    public function testGetDefinitionInHandlerConfigurator(): void
    {
        $handler = new FixtureTestHandler('Config', FixtureDefaultConfigInterface::class);
        $loader  = $this->makeConfiguredLoaderInstance(
            [$this->getFixturePath(__CLASS__) . 'DefaultConfig'],
            [$handler],
        );
        $loader->load();

        self::assertTrue($handler->configureCalled);
        self::assertTrue($handler->prepareCalled);
        self::assertTrue($handler->finishCalled);
        self::assertCount(2, $handler->classes);

        self::assertInstanceOf(HandlerConfigurator::class, $handler->configurator);
        self::assertInstanceOf(HandlerDefinition::class, $handler->configurator->getDefinition());
    }

    public function testContextAwareTrait(): void
    {
        $handler = new FixtureTestHandler('Config', FixtureDefaultConfigInterface::class);
        $handler->setConfigContext(new ConfigContext());
        self::assertInstanceOf(ConfigContext::class, $handler->getContext());
    }
}
