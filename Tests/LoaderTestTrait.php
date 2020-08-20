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
 * Last modified: 2020.07.13 at 15:51
 */

declare(strict_types=1);


namespace Neunerlei\ConfigTests;


use Neunerlei\ConfigTests\Fixture\FixtureTestHandler;
use Neunerlei\Configuration\Finder\HandlerFinder;
use Neunerlei\Configuration\Handler\ConfigHandlerInterface;
use Neunerlei\Configuration\Handler\HandlerDefinition;
use Neunerlei\Configuration\Loader\ConfigContext;
use Neunerlei\Configuration\Loader\Loader;
use Neunerlei\Configuration\Loader\LoaderContext;
use Neunerlei\Configuration\State\ConfigState;
use ReflectionObject;
use SplFileInfo;

trait LoaderTestTrait
{
    /**
     * Creates a new, not configured loader instance
     *
     * @return \Neunerlei\Configuration\Loader\Loader
     */
    protected function makeEmptyLoaderInstance(): Loader
    {
        return new Loader('testCase', 'test');
    }
    
    /**
     * Creates a new, preconfigured loader instance
     *
     * @param   array|null  $rootLocations        Optional root locations to register
     * @param   array|null  $handlersOrLocations  Optional handler instances or locations to register
     *
     * @return \Neunerlei\Configuration\Loader\Loader
     */
    protected function makeConfiguredLoaderInstance(
        ?array $rootLocations = null,
        ?array $handlersOrLocations = null
    ): Loader {
        $loader                       = $this->makeEmptyLoaderInstance();
        $loaderContext                = $this->getLoaderContext($loader);
        $loaderContext->configContext = new ConfigContext();
        $loaderContext->configContext->initialize($loaderContext, new ConfigState([]));
        
        if (is_array($rootLocations)) {
            foreach ($rootLocations as $rootLocation) {
                if (is_array($rootLocation)) {
                    $loader->registerRootLocation(reset($rootLocation), end($rootLocation));
                } else {
                    $loader->registerRootLocation($rootLocation);
                }
            }
        }
        
        if (is_array($handlersOrLocations)) {
            foreach ($handlersOrLocations as $handlerOrLocation) {
                if ($handlerOrLocation instanceof ConfigHandlerInterface) {
                    $loader->registerHandler($handlerOrLocation);
                } else {
                    $loader->registerHandlerLocation($handlerOrLocation);
                }
            }
        }
        
        return $loader;
    }
    
    /**
     * Extracts the loader context from a loader instance
     *
     * @param   \Neunerlei\Configuration\Loader\Loader  $loader
     *
     * @return \Neunerlei\Configuration\Loader\LoaderContext
     */
    protected function getLoaderContext(Loader $loader): LoaderContext
    {
        $ref  = new ReflectionObject($loader);
        $prop = $ref->getProperty('loaderContext');
        $prop->setAccessible(true);
        
        return $prop->getValue($loader);
    }
    
    /**
     * Returns the root path of the package (we assume it is where you call your test script from)
     *
     * @return string
     */
    protected function getPackageRootPath(): string
    {
        return getcwd() . '/';
    }
    
    /**
     * Returns the absolute path to the test directory
     *
     * @return string
     */
    protected function getTestPath(): string
    {
        return $this->getPackageRootPath() . 'Tests/';
    }
    
    /**
     * Returns the absolute path to the example directory
     *
     * @return string
     */
    protected function getExamplePath(): string
    {
        return $this->getPackageRootPath() . 'Example/';
    }
    
    /**
     * Returns the path to the fixture directory of a single test class
     *
     * @param   string  $testClassName  The __CLASS__ variable inside a test
     *
     * @return string
     */
    protected function getFixturePath(string $testClassName): string
    {
        $classNameParts = explode('\\', $testClassName);
        $classBaseName  = end($classNameParts);
        
        return $this->getTestPath() . 'Fixture/' . $classBaseName . '/';
    }
    
    /**
     * Registers the example locations as root locations
     *
     * @param   \Neunerlei\Configuration\Loader\Loader  $loader
     */
    protected function registerExampleRootLocations(Loader $loader): void
    {
        $examplePath = $this->getExamplePath();
        $loader->registerRootLocation(
            $examplePath . 'Plugins/*',
            static function (SplFileInfo $fileInfo) {
                return ucfirst($fileInfo->getFilename());
            });
        $loader->registerRootLocation(
            $examplePath . 'Project/',
            'project');
        $loader->registerRootLocation(
            $examplePath
        );
    }
    
    /**
     * Returns the example content element handler definition
     *
     * @param   \Neunerlei\Configuration\Loader\Loader  $loader
     * @param   string|null                             $handlerClass
     *
     * @return \Neunerlei\Configuration\Handler\HandlerDefinition
     */
    protected function getHandlerDefinition(Loader $loader, ?string $handlerClass = null): HandlerDefinition
    {
        $context  = $this->getLoaderContext($loader);
        $handlers = (new HandlerFinder())->find($context);
        
        return $handlers[$handlerClass ?? FixtureTestHandler::class];
    }
}
