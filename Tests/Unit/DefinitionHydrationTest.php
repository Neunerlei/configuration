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
 * Last modified: 2020.07.14 at 18:35
 */

declare(strict_types=1);


namespace Neunerlei\ConfigTests\Unit;


use Neunerlei\ConfigExample\Plugins\plugin3\ContentElement\TextContentElement;
use Neunerlei\ConfigExample\Project\ContentElement\ImageContentElement;
use Neunerlei\ConfigExample\Project\Handler\ContentElement\ExampleConfigureContentElementInterface;
use Neunerlei\ConfigExample\Project\Handler\ContentElement\ExampleContentElementHandler;
use Neunerlei\ConfigExample\Project\Handler\ContentElement\NonExistingConfigureContentElementExampleInterface;
use Neunerlei\ConfigTests\LoaderTestTrait;
use Neunerlei\ConfigTests\TestHelperTrait;
use Neunerlei\Configuration\Finder\ConfigFinder;
use Neunerlei\Configuration\Loader\ConfigDefinition;
use PHPUnit\Framework\TestCase;

;

/**
 * Class DefinitionHydrationTest
 *
 * Tests both the ConfigDefinition and HandlerDefinition classes for correct hydration / dehydration
 *
 * @package Neunerlei\ConfigTests\Unit
 */
class DefinitionHydrationTest extends TestCase
{
    use TestHelperTrait;
    use LoaderTestTrait;

    public function testDeAndHydration(): void
    {
        $loader = $this->makeConfiguredLoaderInstance([], ['Handler']);
        $this->registerExampleRootLocations($loader);
        $context    = $this->getLoaderContext($loader);
        $definition = $this->getHandlerDefinition($loader, ExampleContentElementHandler::class);
        $finder     = new ConfigFinder();

        $configDefinition = $finder->find($definition, $context->configContext);

        // Test the dehydration
        $dehydratedDefinition = $configDefinition->dehydrate();
        self::assertEquals([
            'handlerDefinition'     =>
                [
                    'className'            => ExampleContentElementHandler::class,
                    'handler'              => ExampleContentElementHandler::class,
                    'allowOverride'        => true,
                    'overrideLocations'    => [],
                    'locations'            => ['ContentElement',],
                    'interfaces'           =>
                        [
                            ExampleConfigureContentElementInterface::class,
                            NonExistingConfigureContentElementExampleInterface::class,
                        ],
                    'overrides'            => [],
                    'defaultState'         => [],
                    'defaultConfigClasses' => [],
                    'after'                => [],
                    'before'               => [],
                ],
            'configContext'         => null,
            'configClasses'         =>
                [
                    TextContentElement::class,
                    ImageContentElement::class,
                    \Neunerlei\ConfigExample\Project\ContentElement\Override\TextContentElement::class,
                ],
            'overrideConfigClasses' =>
                [
                    \Neunerlei\ConfigExample\Project\ContentElement\Override\TextContentElement::class,
                ],
            'classNamespaceMap'     =>
                [
                    TextContentElement::class                                                          => 'Plugin3',
                    ImageContentElement::class                                                         => 'project',
                    \Neunerlei\ConfigExample\Project\ContentElement\Override\TextContentElement::class => 'project',
                ],
        ], $dehydratedDefinition);

        // Check if we can json serialize the definition without problems
        $jsonDefinition = json_encode($dehydratedDefinition, JSON_THROW_ON_ERROR);
        self::assertEquals($dehydratedDefinition, json_decode($jsonDefinition, true, 512, JSON_THROW_ON_ERROR));

        // Rehydrate the definition
        $rehydratedConfigDefinition = ConfigDefinition::hydrate($context, $dehydratedDefinition);
        self::assertEquals($configDefinition, $rehydratedConfigDefinition);
        self::assertInstanceOf(ExampleContentElementHandler::class,
            $this->getValue($rehydratedConfigDefinition, 'handlerDefinition')->handler);

        // Try rehydration with a existing handler instance that was registered on the loader
        $handler = new ExampleContentElementHandler();
        $loader->registerHandler($handler);
        $rehydratedConfigDefinition = ConfigDefinition::hydrate($context, $dehydratedDefinition);
        self::assertSame($handler, $this->getValue($rehydratedConfigDefinition, 'handlerDefinition')->handler);
    }
}
