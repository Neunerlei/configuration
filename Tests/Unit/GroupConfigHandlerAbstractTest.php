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
 * Last modified: 2020.07.14 at 19:03
 */

declare(strict_types=1);


namespace Neunerlei\ConfigTests\Unit;


use Neunerlei\ConfigTests\Fixture\GroupConfigHandlerAbstractTest\FixtureGroupItemHandler;
use Neunerlei\ConfigTests\Fixture\GroupConfigHandlerAbstractTest\Items\Item1;
use Neunerlei\ConfigTests\Fixture\GroupConfigHandlerAbstractTest\Items\Item2;
use Neunerlei\ConfigTests\Fixture\GroupConfigHandlerAbstractTest\Items\Item3;
use Neunerlei\ConfigTests\Fixture\GroupConfigHandlerAbstractTest\Items\Item4;
use Neunerlei\ConfigTests\Fixture\GroupConfigHandlerAbstractTest\Items\Item5;
use Neunerlei\ConfigTests\LoaderTestTrait;
use Neunerlei\Configuration\Finder\ConfigFinder;
use PHPUnit\Framework\TestCase;
use SplFileInfo;

class GroupConfigHandlerAbstractTest extends TestCase
{
    use LoaderTestTrait;

    public function testHandler(): void
    {
        $handler    = new FixtureGroupItemHandler();
        $loader     = $this->makeConfiguredLoaderInstance([
            [
                $this->getFixturePath(__CLASS__),
                static function (SplFileInfo $location, string $className, SplFileInfo $classFile): string {
                    return $classFile->getBasename('.php');
                },
            ],
        ], [$handler]);
        $context    = $this->getLoaderContext($loader);
        $definition = $this->getHandlerDefinition($loader, FixtureGroupItemHandler::class);
        $finder     = new ConfigFinder();

        $configDefinition = $finder->find($definition, $context->configContext);
        $configDefinition->process();

        self::assertEquals([
            ['Item1', Item1::class, '1'],
            ['Item2', Item2::class, '1'],
            ['Item3', Item3::class, '2'],
            ['Item4', Item4::class, '2'],
            ['Item5', Item5::class, '3'],
        ], $handler->groupKeyClasses);
        self::assertEquals([
            ['Item1', '1', [Item1::class, Item2::class]],
            ['Item3', '2', [Item3::class, Item4::class]],
            ['Item5', '3', [Item5::class]],
        ], $handler->preparedGroups);
        self::assertEquals([
            1 => [
                ['Item1', Item1::class],
                ['Item2', Item2::class],
            ],
            2 => [
                ['Item3', Item3::class],
                ['Item4', Item4::class],
            ],
            3 => [
                ['Item5', Item5::class],
            ],
        ], $handler->handledClasses);
        self::assertEquals([
            ['Item1', '1', [Item1::class, Item2::class]],
            ['Item3', '2', [Item3::class, Item4::class]],
            ['Item5', '3', [Item5::class]],
        ], $handler->finishedGroups);

        self::assertTrue($handler->prepareHandlerCalled);
        self::assertTrue($handler->finishHandlerCalled);

    }

}
