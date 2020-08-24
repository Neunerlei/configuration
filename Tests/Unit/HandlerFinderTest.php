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
 * Last modified: 2020.07.13 at 15:48
 */

declare(strict_types=1);


namespace Neunerlei\ConfigTests\Unit;


use FilesystemIterator;
use Neunerlei\ConfigExample\Plugins\plugin1\Handler\PluginHandler\ExamplePluginHandler;
use Neunerlei\ConfigExample\Project\Handler\ContentElement\ExampleContentElementHandler;
use Neunerlei\ConfigExample\Project\Handler\NoopHandler\ExampleNoopHandler;
use Neunerlei\ConfigExample\Project\Handler\RuntimeHandler\ExampleRuntimeHandler;
use Neunerlei\ConfigExample\Project\Handler\RuntimeHandlerInterface;
use Neunerlei\ConfigTests\Fixture\HandlerFinderTest\AbsolutePath\FixtureAbsoluteTestHandler;
use Neunerlei\ConfigTests\Fixture\HandlerFinderTest\AbsolutePath\FixtureNoopAbsoluteTestHandler;
use Neunerlei\ConfigTests\Fixture\HandlerFinderTest\Iterator\FixtureIteratorHandler;
use Neunerlei\ConfigTests\Fixture\HandlerFinderTest\Iterator\FixtureIteratorHandlerOverride;
use Neunerlei\ConfigTests\Fixture\HandlerFinderTest\Iterator\FixtureIteratorHandlerOverride2;
use Neunerlei\ConfigTests\Fixture\HandlerFinderTest\RelativePath\Item1\Handler\FixtureRelativePathHandler1;
use Neunerlei\ConfigTests\Fixture\HandlerFinderTest\RelativePath\Item2\Handler\Deep\FixtureRelativePathHandler3;
use Neunerlei\ConfigTests\Fixture\HandlerFinderTest\RelativePath\Item2\Handler\FixtureRelativePathHandler2;
use Neunerlei\ConfigTests\Fixture\HandlerFinderTest\RelativePath\Item3\DeepHandler\Deep\FixtureRelativePathHandler4;
use Neunerlei\ConfigTests\Fixture\HandlerFinderTest\Statics\FixtureStaticHandler;
use Neunerlei\ConfigTests\LoaderTestTrait;
use Neunerlei\ConfigTests\TestHelperTrait;
use Neunerlei\Configuration\Exception\HandlerClassNotAutoloadableException;
use Neunerlei\Configuration\Finder\FilteredHandlerFinder;
use Neunerlei\Configuration\Finder\HandlerFinder;
use Neunerlei\Configuration\Handler\ConfigHandlerInterface;
use Neunerlei\Configuration\Handler\HandlerDefinition;
use Neunerlei\Configuration\Loader\Loader;
use PHPUnit\Framework\TestCase;

class HandlerFinderTest extends TestCase
{
    use TestHelperTrait;
    use LoaderTestTrait;

    public function testPrepareHandlerLocations(): void
    {
        $loader  = $this->makeTestLoader();
        $context = $this->getLoaderContext($loader);
        $finder  = new HandlerFinder();
        $caller  = $this->makeCaller($finder, 'prepareHandlerLocations');

        $handlerLocations = $caller($context);
        $list             = iterator_to_array($handlerLocations);
        $basePath         = $this->getFixturePath(__CLASS__);
        self::assertEquals([
            $basePath . 'AbsolutePath',
            $basePath . 'Iterator/FixtureIteratorHandler.php',
            $basePath . 'Iterator/FixtureIteratorHandlerOverride.php',
            $basePath . 'Iterator/FixtureIteratorHandlerOverride2.php',
            $basePath . 'RelativePath/Item1/Handler',
            $basePath . 'RelativePath/Item2/Handler',
            $basePath . 'RelativePath/Item3/DeepHandler/Deep/FixtureRelativePathHandler4.php',
        ], array_keys($list));
    }

    public function testFindHandlerClasses(): void
    {
        $loader  = $this->makeTestLoader();
        $context = $this->getLoaderContext($loader);
        $finder  = new HandlerFinder();
        $caller  = $this->makeCaller($finder, 'findHandlerClasses');

        $classes = $caller($context);
        self::assertEquals([
            FixtureAbsoluteTestHandler::class,
            // Yes, this is correct here, because we have to find and load it, in order to remove it in the override process.
            FixtureNoopAbsoluteTestHandler::class,
            FixtureIteratorHandler::class,
            FixtureIteratorHandlerOverride::class,
            FixtureIteratorHandlerOverride2::class,
            FixtureRelativePathHandler1::class,
            FixtureRelativePathHandler2::class,
            FixtureRelativePathHandler3::class,
            FixtureRelativePathHandler4::class,
        ], $classes);
    }

    public function testLoadOverrideAndSorting(): void
    {
        $loader  = $this->makeTestLoader();
        $context = $this->getLoaderContext($loader);
        $finder  = new HandlerFinder();

        // Register a single static handler
        $loader->registerHandler(new FixtureStaticHandler());

        $handlers = $finder->find($context);
        self::assertEquals(
            [
                FixtureRelativePathHandler1::class,
                FixtureStaticHandler::class,
                FixtureAbsoluteTestHandler::class,
                FixtureIteratorHandlerOverride2::class,
                FixtureRelativePathHandler3::class,
                FixtureRelativePathHandler4::class,
                FixtureRelativePathHandler2::class,
            ], array_keys($handlers));
        self::assertContainsOnlyInstancesOf(HandlerDefinition::class, $handlers);
        foreach ($handlers as $handler) {
            self::assertInstanceOf(ConfigHandlerInterface::class, $handler->handler);
        }
    }

    public function testFilteredHandlerFinder(): void
    {
        $loader = $this->makeTestLoader();
        $loader->clearRootLocations();
        $this->registerExampleRootLocations($loader);
        $context = $this->getLoaderContext($loader);

        // Test how we ignore existing handlers
        $loader->registerHandlerLocation($this->getFixturePath(__CLASS__) . 'Statics');

        // Register a single static handler
        $loader->registerHandler(new FixtureStaticHandler());

        // Find only handlers with the runtime interface
        $finder   = new FilteredHandlerFinder([], [RuntimeHandlerInterface::class]);
        $handlers = $finder->find($context);
        self::assertEquals(
            [
                FixtureStaticHandler::class,
                ExampleRuntimeHandler::class,
            ], array_keys($handlers));
        self::assertContainsOnlyInstancesOf(HandlerDefinition::class, $handlers);

        // Find only handlers WITHOUT the runtime interface
        $finder   = new FilteredHandlerFinder([RuntimeHandlerInterface::class], []);
        $handlers = $finder->find($context);
        self::assertEquals(
            [
                FixtureAbsoluteTestHandler::class,
                FixtureIteratorHandlerOverride2::class,
                ExamplePluginHandler::class,
                ExampleContentElementHandler::class,
                FixtureStaticHandler::class,
                ExampleNoopHandler::class,
            ], array_keys($handlers));
        self::assertContainsOnlyInstancesOf(HandlerDefinition::class, $handlers);
        foreach ($handlers as $handler) {
            self::assertInstanceOf(ConfigHandlerInterface::class, $handler->handler);
        }
    }

    public function testFailOnUnloadableClass(): void
    {
        $this->expectException(HandlerClassNotAutoloadableException::class);
        $loader  = $this->makeConfiguredLoaderInstance([
            $this->getFixturePath(__CLASS__) . 'Unloadable/*',
        ], ['Handler']);
        $context = $this->getLoaderContext($loader);
        $finder  = new HandlerFinder();
        $finder->find($context);
    }

    protected function makeTestLoader(): Loader
    {
        $basePath = $this->getFixturePath(__CLASS__);
        $loader   = $this->makeConfiguredLoaderInstance([$basePath . 'RelativePath/*']);

        $loader->registerHandlerLocation($basePath . 'AbsolutePath');
        $loader->registerHandlerLocation(new FilesystemIterator($basePath . 'Iterator'));
        $loader->registerHandlerLocation('Handler');
        $loader->registerHandlerLocation('DeepHandler/*/*');

        return $loader;
    }
}
