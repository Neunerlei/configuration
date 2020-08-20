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
 * Last modified: 2020.07.13 at 19:54
 */

declare(strict_types=1);


namespace Neunerlei\ConfigTests\Unit;


use Neunerlei\ConfigTests\Fixture\FixtureTestHandler;
use Neunerlei\ConfigTests\Fixture\ModifierTest\General\Config\FixtureTestModifierConfig1;
use Neunerlei\ConfigTests\Fixture\ModifierTest\General\Config\FixtureTestModifierConfig2;
use Neunerlei\ConfigTests\Fixture\ModifierTest\General\Config\FixtureTestModifierConfig3;
use Neunerlei\ConfigTests\Fixture\ModifierTest\General\Config\FixtureTestModifierConfig4;
use Neunerlei\ConfigTests\Fixture\ModifierTest\General\FixtureTestModifier;
use Neunerlei\ConfigTests\Fixture\ModifierTest\Order\FixtureOrderConfig1;
use Neunerlei\ConfigTests\Fixture\ModifierTest\Order\FixtureOrderConfig2;
use Neunerlei\ConfigTests\Fixture\ModifierTest\Order\FixtureOrderConfig3;
use Neunerlei\ConfigTests\Fixture\ModifierTest\Order\FixtureOrderConfig4;
use Neunerlei\ConfigTests\Fixture\ModifierTest\Order\Override\FixtureOrderConfig5;
use Neunerlei\ConfigTests\Fixture\ModifierTest\Order\Override\FixtureOrderConfig6;
use Neunerlei\ConfigTests\Fixture\ModifierTest\Order\Override\FixtureOrderConfig7;
use Neunerlei\ConfigTests\Fixture\ModifierTest\Replace\FixtureReplaceConfig1;
use Neunerlei\ConfigTests\Fixture\ModifierTest\Replace\FixtureReplaceConfig3;
use Neunerlei\ConfigTests\Fixture\ModifierTest\Replace\Override\FixtureReplaceConfig4;
use Neunerlei\ConfigTests\LoaderTestTrait;
use Neunerlei\ConfigTests\TestHelperTrait;
use Neunerlei\Configuration\Finder\ConfigFinder;
use Neunerlei\Configuration\Loader\Loader;
use Neunerlei\Configuration\Modifier\ModifierContext;
use PHPUnit\Framework\TestCase;

class ModifierTest extends TestCase
{
    use TestHelperTrait;
    use LoaderTestTrait;

    /**
     * Tests if modifiers are executed correctly
     */
    public function testGeneralModifierExecution(): void
    {
        $loader   = $this->makeTestLoader('General');
        $modifier = new FixtureTestModifier();
        $loader->registerModifier($modifier);

        $context    = $this->getLoaderContext($loader);
        $definition = $this->getHandlerDefinition($loader);
        $finder     = new ConfigFinder();
        $finder->find($definition, $context->configContext);

        // Test if the modifier was executed correctly
        self::assertEquals(1, $modifier->getKeyCalled);
        self::assertEquals(1, $modifier->applyCalled);
        self::assertInstanceOf(ModifierContext::class, $modifier->modifierContext);

        // Test if the context returns all values
        /** @var ModifierContext $modifierContext */
        $modifierContext = $modifier->modifierContext;

        self::assertEquals([
            FixtureTestModifierConfig1::class => 'fixtures',
            FixtureTestModifierConfig2::class => 'fixtures',
            FixtureTestModifierConfig3::class => 'fixtures',
            FixtureTestModifierConfig4::class => 'fixtures',
        ], $modifierContext->getClassNamespaceMap());

        self::assertEquals([], $modifierContext->getOverrideConfigClasses());

        self::assertEquals([
            FixtureTestModifierConfig1::class,
            FixtureTestModifierConfig3::class,
            FixtureTestModifierConfig4::class,
            FixtureTestModifierConfig2::class,
        ], $modifierContext->getConfigClasses());

        self::assertSame($context->configContext, $modifierContext->getConfigContext());

        self::assertSame($definition, $modifierContext->getHandlerDefinition());

        // Test setting of the class namespace map
        $modifierContext->setClassNamespaceMap(['foo' => 'bar']);
        self::assertEquals(['foo' => 'bar'], $modifierContext->getClassNamespaceMap());
    }

    /**
     * Tests the built in order modifier
     */
    public function testOrderModifier(): void
    {
        self::assertEquals(
            [
                FixtureOrderConfig3::class,
                FixtureOrderConfig2::class,
                FixtureOrderConfig1::class,
                FixtureOrderConfig5::class,
                FixtureOrderConfig4::class,
                FixtureOrderConfig6::class,
                FixtureOrderConfig7::class,
            ],
            $this->findClassList('order')
        );
    }

    /**
     * Tests the built in replace modifier
     */
    public function testReplaceModifier(): void
    {
        self::assertEquals(
            [
                FixtureReplaceConfig1::class,
                FixtureReplaceConfig4::class,
                FixtureReplaceConfig3::class,
            ],
            $this->findClassList('replace')
        );
    }

    protected function makeTestLoader(string $type): Loader
    {
        return $this->makeConfiguredLoaderInstance(
            [[$this->getFixturePath(__CLASS__), 'fixtures']],
            [new FixtureTestHandler(ucfirst($type))]
        );
    }

    protected function findClassList(string $type): array
    {
        $loader     = $this->makeTestLoader($type);
        $context    = $this->getLoaderContext($loader);
        $definition = $this->getHandlerDefinition($loader);
        $finder     = new ConfigFinder();
        $confDef    = $finder->find($definition, $context->configContext);

        return $this->getValue($confDef, 'configClasses');
    }
}
