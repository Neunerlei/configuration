<?php
/*
 * Copyright 2021 LABOR.digital
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
 * Last modified: 2021.01.30 at 17:47
 */

declare(strict_types=1);


namespace Neunerlei\ConfigTests\Unit;


use Neunerlei\ConfigExample\Project\ContentElement\ImageContentElement;
use Neunerlei\ConfigExample\Project\ContentElement\Override\TextContentElement;
use Neunerlei\ConfigExample\Project\Handler\ContentElement\ExampleContentElementHandler;
use Neunerlei\ConfigTests\LoaderTestTrait;
use Neunerlei\Configuration\Finder\ConfigFinder;
use PHPUnit\Framework\TestCase;

class DefinitionMethodTest extends TestCase
{
    use LoaderTestTrait;

    public function testPublicMethods()
    {
        $loader = $this->makeConfiguredLoaderInstance([], ['Handler']);
        $this->registerExampleRootLocations($loader);
        $context    = $this->getLoaderContext($loader);
        $definition = $this->getHandlerDefinition($loader, ExampleContentElementHandler::class);
        $finder     = new ConfigFinder();

        $configDefinition = $finder->find($definition, $context->configContext);

        $dehydratedDefinition = $configDefinition->dehydrate();

        self::assertEquals($dehydratedDefinition['classNamespaceMap'], $configDefinition->getClassNamespaceMap());
        self::assertEquals($dehydratedDefinition['configClasses'], $configDefinition->getConfigClasses());
        self::assertEquals($dehydratedDefinition['overrideConfigClasses'],
            $configDefinition->getOverrideConfigClasses());
        self::assertSame($definition, $configDefinition->getHandlerDefinition());
        self::assertTrue($configDefinition->isOverride(TextContentElement::class));
        self::assertFalse($configDefinition->isOverride(ImageContentElement::class));

    }
}
