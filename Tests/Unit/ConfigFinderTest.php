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
 * Last modified: 2020.07.13 at 15:47
 */

declare(strict_types=1);


namespace Neunerlei\ConfigTests\Unit;


use Neunerlei\ConfigTests\Fixture\ConfigFinderTest\ClassNamespaceItems\Item1\Config\FixtureConfigFinderNsConfig1;
use Neunerlei\ConfigTests\Fixture\ConfigFinderTest\ClassNamespaceItems\Item1\Config\FixtureConfigFinderNsConfig2;
use Neunerlei\ConfigTests\Fixture\ConfigFinderTest\ClassNamespaceItems\Item2\Config\FixtureConfigFinderNsConfig3;
use Neunerlei\ConfigTests\Fixture\ConfigFinderTest\ClassNamespaceItems\Item2\Config\FixtureConfigFinderNsConfig4;
use Neunerlei\ConfigTests\Fixture\ConfigFinderTest\Items\Item1\Config\FixtureConfigFinderConfig1;
use Neunerlei\ConfigTests\Fixture\ConfigFinderTest\Items\Item2\Config\FixtureConfigFinderConfig2;
use Neunerlei\ConfigTests\Fixture\ConfigFinderTest\Items\Item2\Config\Override\FixtureConfigFinderConfig4;
use Neunerlei\ConfigTests\Fixture\ConfigFinderTest\Items\Item3\Config\AltOverride\FixtureConfigFinderConfig6;
use Neunerlei\ConfigTests\Fixture\ConfigFinderTest\Items\Item3\Config\FixtureConfigFinderConfig3;
use Neunerlei\ConfigTests\Fixture\ConfigFinderTest\Items\Item3\Config\Override\FixtureConfigFinderConfig5;
use Neunerlei\ConfigTests\Fixture\FixtureTestHandler;
use Neunerlei\ConfigTests\LoaderTestTrait;
use Neunerlei\ConfigTests\TestHelperTrait;
use Neunerlei\Configuration\Exception\ConfigClassNotAutoloadableException;
use Neunerlei\Configuration\Finder\ConfigFinder;
use Neunerlei\Configuration\Handler\HandlerConfigurator;
use Neunerlei\Configuration\Loader\Loader;
use Neunerlei\Configuration\Loader\NamespaceAwareSplFileInfo;
use PHPUnit\Framework\TestCase;
use SplFileInfo;

class ConfigFinderTest extends TestCase
{
    use TestHelperTrait;
    use LoaderTestTrait;
    
    public function provideTestFindClassesData(): array
    {
        return [
            // Default behaviour
            [
                null,
                [
                    // Merged classes
                    [
                        FixtureConfigFinderConfig1::class,
                        FixtureConfigFinderConfig2::class,
                        // 6 before 3, because the sorting takes place alphabetically including the directory
                        // So AltOverride comes earlier than the root directory
                        FixtureConfigFinderConfig6::class,
                        FixtureConfigFinderConfig3::class,
                        FixtureConfigFinderConfig4::class,
                        FixtureConfigFinderConfig5::class,
                    ],
                    // Override classes
                    [
                        FixtureConfigFinderConfig4::class,
                        FixtureConfigFinderConfig5::class,
                    ],
                    // Namespace mapping
                    [
                        FixtureConfigFinderConfig1::class => 'Item1',
                        FixtureConfigFinderConfig2::class => 'Item2',
                        FixtureConfigFinderConfig4::class => 'Item2',
                        FixtureConfigFinderConfig6::class => 'Item3',
                        FixtureConfigFinderConfig3::class => 'Item3',
                        FixtureConfigFinderConfig5::class => 'Item3',
                    ],
                ],
            ],
            // Modified Override directory
            [
                function (HandlerConfigurator $configurator) {
                    $configurator->registerOverrideLocation('AltOverride');
                },
                [
                    // Merged classes
                    [
                        FixtureConfigFinderConfig1::class,
                        FixtureConfigFinderConfig2::class,
                        // 4 before 3 because 2 and 4 come from Item2 while 3 comes from Item3, so this is fine
                        FixtureConfigFinderConfig4::class,
                        FixtureConfigFinderConfig3::class,
                        FixtureConfigFinderConfig5::class,
                        FixtureConfigFinderConfig6::class,
                    ],
                    // Override classes
                    [
                        FixtureConfigFinderConfig6::class,
                    ],
                    // Namespace mapping
                    [
                        FixtureConfigFinderConfig1::class => 'Item1',
                        FixtureConfigFinderConfig2::class => 'Item2',
                        FixtureConfigFinderConfig4::class => 'Item2',
                        FixtureConfigFinderConfig6::class => 'Item3',
                        FixtureConfigFinderConfig3::class => 'Item3',
                        FixtureConfigFinderConfig5::class => 'Item3',
                    ],
                ],
            ],
            
            // No Overrides at all
            [
                function (HandlerConfigurator $configurator) {
                    $configurator->setAllowOverride(false);
                },
                [
                    // Merged classes
                    [
                        FixtureConfigFinderConfig1::class,
                        FixtureConfigFinderConfig2::class,
                        // 4 before 3 because 2 and 4 come from Item2 while 3 comes from Item3, so this is fine
                        FixtureConfigFinderConfig4::class,
                        // 6 before 3, because the sorting takes place alphabetically including the directory
                        // So AltOverride comes earlier than the root directory
                        FixtureConfigFinderConfig6::class,
                        FixtureConfigFinderConfig3::class,
                        FixtureConfigFinderConfig5::class,
                    ],
                    // Override classes
                    [
                    ],
                    // Namespace mapping
                    [
                        FixtureConfigFinderConfig1::class => 'Item1',
                        FixtureConfigFinderConfig2::class => 'Item2',
                        FixtureConfigFinderConfig4::class => 'Item2',
                        FixtureConfigFinderConfig6::class => 'Item3',
                        FixtureConfigFinderConfig3::class => 'Item3',
                        FixtureConfigFinderConfig5::class => 'Item3',
                    ],
                ],
            ],
        ];
    }
    
    /**
     * @param   callable|null  $handlerConfig
     * @param   array          $expectedResult
     *
     * @dataProvider provideTestFindClassesData
     */
    public function testFindClasses(?callable $handlerConfig, array $expectedResult): void
    {
        $loader     = $this->makeTestLoader($handlerConfig);
        $context    = $this->getLoaderContext($loader);
        $definition = $this->getHandlerDefinition($loader);
        $finder     = new ConfigFinder();
        $caller     = $this->makeCaller($finder, 'findClasses');
        
        $this->assertEquals($expectedResult, $caller($definition, $context->configContext));
    }
    
    public function testClassNamespaceGeneration()
    {
        $loader = $this->makeTestLoader();
        $loader->clearRootLocations();
        $context    = $this->getLoaderContext($loader);
        $definition = $this->getHandlerDefinition($loader);
        $finder     = new ConfigFinder();
        $caller     = $this->makeCaller($finder, 'findClasses');
        
        $fixturePath = $this->getFixturePath(__CLASS__);
        $loader->registerRootLocation($fixturePath . 'ClassNamespaceItems/*',
            function ($location, $className, $classFile) use ($fixturePath): string {
                $this->assertInstanceOf(NamespaceAwareSplFileInfo::class, $location);
                $this->assertInstanceOf(NamespaceAwareSplFileInfo::class, $classFile);
                $this->assertIsString($className);
                $this->assertContains($className, [
                    FixtureConfigFinderNsConfig1::class,
                    FixtureConfigFinderNsConfig2::class,
                    FixtureConfigFinderNsConfig3::class,
                    FixtureConfigFinderNsConfig4::class,
                ]);
                if ($className === FixtureConfigFinderNsConfig1::class) {
                    $this->assertEquals($fixturePath . 'ClassNamespaceItems/Item1', $location->getPathname());
                    $this->assertEquals(
                        $fixturePath . 'ClassNamespaceItems/Item1/Config/FixtureConfigFinderNsConfig1.php',
                        $classFile->getPathname());
                    
                    return 'fixture1';
                }
                if ($className === FixtureConfigFinderNsConfig2::class) {
                    $this->assertEquals($fixturePath . 'ClassNamespaceItems/Item1', $location->getPathname());
                    $this->assertEquals(
                        $fixturePath . 'ClassNamespaceItems/Item1/Config/FixtureConfigFinderNsConfig2.php',
                        $classFile->getPathname());
                    
                    return 'fixture2';
                }
                if ($className === FixtureConfigFinderNsConfig3::class) {
                    $this->assertEquals($fixturePath . 'ClassNamespaceItems/Item2', $location->getPathname());
                    $this->assertEquals(
                        $fixturePath . 'ClassNamespaceItems/Item2/Config/FixtureConfigFinderNsConfig3.php',
                        $classFile->getPathname());
                    
                    return 'fixture3';
                }
                if ($className === FixtureConfigFinderNsConfig4::class) {
                    $this->assertEquals($fixturePath . 'ClassNamespaceItems/Item2', $location->getPathname());
                    $this->assertEquals(
                        $fixturePath . 'ClassNamespaceItems/Item2/Config/FixtureConfigFinderNsConfig4.php',
                        $classFile->getPathname());
                    
                    return 'fixture4';
                }
                $this->fail('Invalid class name has been passed to root location');
                
                return '';
            });
        
        $this->assertEquals(
            [
                [
                    FixtureConfigFinderNsConfig1::class,
                    FixtureConfigFinderNsConfig2::class,
                    FixtureConfigFinderNsConfig3::class,
                    FixtureConfigFinderNsConfig4::class,
                ],
                [],
                [
                    FixtureConfigFinderNsConfig1::class => 'fixture1',
                    FixtureConfigFinderNsConfig2::class => 'fixture2',
                    FixtureConfigFinderNsConfig3::class => 'fixture3',
                    FixtureConfigFinderNsConfig4::class => 'fixture4',
                ],
            ],
            $caller($definition, $context->configContext));
    }
    
    public function testFailOnUnloadableClass()
    {
        $this->expectException(ConfigClassNotAutoloadableException::class);
        $loader     = $this->makeConfiguredLoaderInstance([
            $this->getFixturePath(__CLASS__) . 'Unloadable/*',
        ], [new FixtureTestHandler('Config')]);
        $context    = $this->getLoaderContext($loader);
        $definition = $this->getHandlerDefinition($loader);
        $finder     = new ConfigFinder();
        $caller     = $this->makeCaller($finder, 'findClasses');
        $caller($definition, $context->configContext);
    }
    
    public function testFind(): void
    {
        $loader     = $this->makeTestLoader();
        $context    = $this->getLoaderContext($loader);
        $definition = $this->getHandlerDefinition($loader);
        $finder     = new ConfigFinder();
        
        $configDefinition = $finder->find($definition, $context->configContext);
        $this->assertInstanceOf(FixtureTestHandler::class,
            $this->getValue($configDefinition, 'handlerDefinition')->handler);
    }
    
    /**
     * Helper to create a test specific loader instance
     *
     * @param   callable|null  $handlerConfig
     *
     * @return \Neunerlei\Configuration\Loader\Loader
     */
    protected function makeTestLoader(?callable $handlerConfig = null): Loader
    {
        return $this->makeConfiguredLoaderInstance(
            [
                [
                    $this->getFixturePath(__CLASS__) . 'Items/*',
                    function (SplFileInfo $fileInfo) {
                        return $fileInfo->getBasename();
                    },
                ],
            ],
            [new FixtureTestHandler('Config', null, $handlerConfig)]
        );
    }
}
