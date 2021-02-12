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
 * Last modified: 2020.07.15 at 22:23
 */

declare(strict_types=1);


namespace Neunerlei\ConfigTests\Functional;


use Neunerlei\ConfigExample\ExampleCacheImplementation;
use Neunerlei\ConfigTests\LoaderTestTrait;
use Neunerlei\Configuration\Loader\Loader;
use Neunerlei\Configuration\State\ConfigState;
use PHPUnit\Framework\TestCase;
use ReflectionObject;

class LoaderFunctionalTest extends TestCase
{
    use LoaderTestTrait;

    public function testUnCachedLoad()
    {
        $loader = $this->makeTestLoader();
        $state  = $loader->load();

        self::assertEquals(
            ['plugin1', 'contentElements', 'runtimeInstances'],
            array_keys($state->getAll())
        );

        $this->applyPlugin1Assertion($state);
        $this->applyContentElementAssertion($state);
        $this->applyRuntimeInstancesAssertion($state);
    }

    public function testNormalLoad()
    {
        $loader = $this->makeTestLoader();
        $cache  = new ExampleCacheImplementation(false);
        $loader->setCache($cache);

        $state = $loader->load();
        $this->applyPlugin1Assertion($state);
        $this->applyContentElementAssertion($state);
        $this->applyRuntimeInstancesAssertion($state);
        self::assertTrue($cache->has('configuration-testCase-test'));
        self::assertIsArray(json_decode($cache->get('configuration-testCase-test'), true, 512, JSON_THROW_ON_ERROR));

        // Reload from cache
        $state = $loader->load();
        $this->applyPlugin1Assertion($state);
        $this->applyContentElementAssertion($state);

        // The runtime instances should exist, but be broken now, because they got serialized into a json object
        self::assertIsArray($state->get('runtimeInstances'));
        foreach ($state->get('runtimeInstances') as $instance) {
            self::assertIsArray($instance);
            self::assertContains($instance['me'], ['runtime class!', 'plugin2']);
        }
    }

    public function testRuntimeLoad()
    {
        $loader = $this->makeTestLoader();
        $cache  = new class(false) extends ExampleCacheImplementation {
            public $getWasCalled = false;

            public function get($key, $default = null)
            {
                $result = parent::get($key, $default);
                if ($result !== $default) {
                    $this->getWasCalled = true;
                }

                return $result;
            }

        };
        $loader->setCache($cache);

        $state = $loader->load(true);
        $this->applyPlugin1Assertion($state);
        $this->applyContentElementAssertion($state);
        $this->applyRuntimeInstancesAssertion($state);
        self::assertFalse($cache->getWasCalled);
        self::assertTrue($cache->has('configuration-testCase-test-runtimeDefinitions'));
        self::assertIsArray(json_decode($cache->get('configuration-testCase-test-runtimeDefinitions'), true, 512,
            JSON_THROW_ON_ERROR));
        $cache->getWasCalled = false;

        // Reload using the definitions from cache
        $state = $loader->load(true);
        self::assertTrue($cache->getWasCalled);
        $this->applyPlugin1Assertion($state);
        $this->applyContentElementAssertion($state);
        $this->applyRuntimeInstancesAssertion($state);
    }

    protected function applyPlugin1Assertion(ConfigState $state): void
    {
        self::assertEquals([
            'option' => 'projectOption',
            'list'   => [
                'Plugin2' => 'plugin 2 value: Yes, you can configure plugins from other plugins',
                'project' => 'projectValue',
            ],
        ], $state->get('plugin1'));
    }

    protected function applyContentElementAssertion(ConfigState $state): void
    {
        self::assertEquals([
            'Text'  => [
                'namespace' => 'Plugin3',
                'key'       => 'Text',
                'title'     => 'Text Element',
                'options'   => [
                    'foo' => 'projectOverride',
                    'bar' => 'baz',
                ],
            ],
            'Image' => [
                'namespace' => 'project',
                'key'       => 'Image',
                'title'     => 'Image element',
                'options'   => [
                    'type' => 'image',
                ],
            ],
        ], $state->get('contentElements'));
    }

    protected function applyRuntimeInstancesAssertion(ConfigState $state): void
    {
        self::assertIsArray($state->get('runtimeInstances'));
        foreach ($state->get('runtimeInstances') as $instance) {
            $ref = new ReflectionObject($instance);
            self::assertTrue($ref->isAnonymous());
            self::assertContains($instance->me, ['runtime class!', 'plugin2']);
        }
    }

    protected function makeTestLoader(): Loader
    {
        $loader = $this->makeConfiguredLoaderInstance([], ['Handler']);
        $this->registerExampleRootLocations($loader);

        return $loader;
    }
}
