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
 * Last modified: 2020.07.13 at 13:09
 */

declare(strict_types=1);


namespace Neunerlei\ConfigTests\Unit;


use Neunerlei\Configuration\State\ConfigState;
use PHPUnit\Framework\TestCase;

class ConfigStateTest extends TestCase
{
    public function provideTestInitialStateData(): array
    {
        return [
            [[]],
            [['foo' => 'bar']],
        ];
    }

    /**
     * @dataProvider provideTestInitialStateData
     *
     * @param   array  $state
     */
    public function testInitialState(array $state): void
    {
        $i = new ConfigState($state);
        self::assertEquals($state, $i->getAll());
    }

    public function testSet(): void
    {
        $i = new ConfigState([]);
        self::assertEquals([], $i->getAll());

        $i->set('foo', 'bar');
        self::assertEquals(['foo' => 'bar'], $i->getAll());

        $i->set('foo', 123);
        self::assertEquals(['foo' => 123], $i->getAll());

        $i->set('foo.bar', 123);
        self::assertEquals(['foo' => ['bar' => 123]], $i->getAll());
    }

    public function testSetMultiple(): void
    {
        $i = new ConfigState([]);
        $i->setMultiple([
            'foo' => 'bar',
            'bar' => 'baz',
            'baz' => [
                'foo' => 123,
                'bar' => 234,
            ],
        ]);
        self::assertEquals([
            'foo' => 'bar',
            'bar' => 'baz',
            'baz' => [
                'foo' => 123,
                'bar' => 234,
            ],
        ], $i->getAll());
    }

    public function testGet(): void
    {
        $i = new ConfigState(['foo' => true, 'bar' => false, 'baz' => 123, 'faz' => ['foo' => 'asdf']]);
        self::assertTrue($i->get('foo'));
        self::assertFalse($i->get('bar'));
        self::assertEquals(123, $i->get('baz'));
        self::assertEquals(['foo' => 'asdf'], $i->get('faz'));
        self::assertEquals('asdf', $i->get('faz.foo'));
        self::assertNull($i->get('notExistent'));
        self::assertNull($i->get('notExistent.too'));
        self::assertTrue($i->get('notExistent', true));
        self::assertTrue($i->get('foo', false));
    }

    public function testHas(): void
    {
        $i = new ConfigState([]);
        self::assertFalse($i->has('foo'));
        self::assertFalse($i->has('bar'));
        $i->set('foo', 123);
        self::assertTrue($i->has('foo'));
        self::assertFalse($i->has('bar'));
        $i->set('bar.baz.foo', 123);
        self::assertTrue($i->has('bar'));
        self::assertTrue($i->has('bar.baz'));
        self::assertTrue($i->has('bar.baz.foo'));
    }

    public function testUseNamespace()
    {
        $i = new ConfigState([]);

        // Simple namespace
        $i->useNamespace('foo', function (ConfigState $i) {
            self::assertInstanceOf(ConfigState::class, $i);
            $i->set('foo', 123);
            $i->set('bar', true);
            self::assertTrue($i->get('bar'));
            self::assertTrue($i->has('foo'));
            self::assertEquals(['foo' => ['foo' => 123, 'bar' => true]], $i->getAll());
        });
        self::assertEquals(['foo' => ['foo' => 123, 'bar' => true]], $i->getAll());

        // Nested namespace
        $i->useNamespace('foo', function () use ($i) {
            // We are back in the previous namespace
            self::assertTrue($i->get('bar'));
            self::assertTrue($i->has('foo'));
            $i->set('parent', true);

            $i->useNamespace('baz', function () use ($i) {
                // Values from the parent namespace
                self::assertNull($i->get('bar'));
                self::assertFalse($i->has('parent'));
                self::assertFalse($i->has('foo'));

                // Set some child values
                $i->set('foo', 456);
                $i->set('bar', 789);

                // Test a third nesting level with a multi-step namespace
                $i->useNamespace('foo.sub.space', function () use ($i) {
                    $i->set('test', true);
                    $i->set('test2', 'asdf');
                });

                // Now go back to the root namespace
                $i->useNamespace(null, function () use ($i) {
                    // We should now have access to all values
                    // "foo" namespace
                    self::assertTrue($i->has('foo'));
                    self::assertTrue($i->get('foo.bar'));
                    self::assertEquals(123, $i->get('foo.foo'));

                    // Nested sub space
                    self::assertTrue($i->has('foo.sub.space'));
                    self::assertTrue($i->get('foo.sub.space.test'));
                    self::assertEquals('asdf', $i->get('foo.sub.space.test2'));

                    // "baz" namespace
                    self::assertTrue($i->has('baz'));
                    self::assertEquals(456, $i->get('baz.foo'));
                    self::assertEquals(789, $i->get('baz.bar'));

                });
            });

            // We should now be back in the parent namespace
            self::assertTrue($i->has('parent'));
        });

        // Now let's check our result
        self::assertEquals([
            'foo' => [
                'foo'    => 123,
                'bar'    => true,
                'sub'    => [
                    'space' => [
                        'test'  => true,
                        'test2' => 'asdf',
                    ],
                ],
                'parent' => true,
            ],
            'baz' => [
                'foo' => 456,
                'bar' => 789,
            ],
        ], $i->getAll());
    }

    public function testMerge()
    {
        $a = new ConfigState(['foo' => 'bar', 'bar' => 'baz', 'baz' => ['subFoo' => ['subFoo2' => true]]]);
        $b = new ConfigState(['bar' => 123, 'baz' => ['subFoo' => ['subFoo1' => 'asdf']]]);
        $c = $a->mergeWith($b);
        self::assertNotSame($a, $c);
        self::assertNotSame($b, $c);
        self::assertEquals([
            'foo' => 'bar',
            'bar' => 123,
            'baz' => [
                'subFoo' => [
                    'subFoo1' => 'asdf',
                    'subFoo2' => true,
                ],
            ],
        ], $c->getAll());
    }

    public function testWatcherSimple()
    {
        $state = new ConfigState([]);
        $i     = 0;
        $state->addWatcher('test', function ($v) use (&$i) {
            static::assertEquals('asdf', $v);
            $i++;
        });
        $state->addWatcher('foo.bar', function ($v) use (&$i) {
            static::assertEquals([123, 234], $v);
            $i++;
        });

        $state->set('test', 'asdf');
        $state->set('foo', ['baz' => 123]);
        $state->set('foo.bar', [123, 234]);

        static::assertEquals([
            'test' => 'asdf',
            'foo'  => [
                'baz' => 123,
                'bar' => [123, 234],
            ],
        ], $state->getAll());

        static::assertEquals(2, $i);
    }

    public function testWatcherComplex(): void
    {
        $state = new ConfigState([]);
        $i     = 0;

        $state->addWatcher('test.bar.baz', function ($v) use (&$i) {
            static::assertEquals(['foo' => 1], $v);
            $i++;
        });
        $state->addWatcher('test', function ($v) use (&$i) {
            static::assertEquals(['bar' => ['baz' => ['foo' => 1]]], $v);
            $i++;
        });
        $state->addWatcher('foo.bar', function ($v) use (&$i) {
            static::assertEquals(1, $v);
            $i++;
        });

        $state->set('test.bar.baz.foo', 1);
        $state->set('foo', ['bar' => 1]);

        static::assertEquals(3, $i);
    }

    public function testWatcherRemoval(): void
    {
        $state = new ConfigState([]);
        $i     = 0;

        $state->addWatcher('test', $c = function () {
            self::fail('Watcher was not removed correctly!');
        });
        $state->addWatcher('test', $c2 = function ($v) use (&$i) {
            self::assertEquals(1, $v);
            $i++;
        });

        $state->removeWatcher($c);

        $state->set('test', 1);

        static::assertEquals(1, $i);

        $i = 0;
        $state->removeWatcher($c2);
        $state->set('test', 2);
        static::assertEquals(0, $i);
    }

    public function testWatcherMerging(): void
    {
        $a = new ConfigState([]);
        $b = new ConfigState([]);
        $i = 0;

        $a->addWatcher('foo', function ($v) use (&$i) {
            self::assertEquals(1, $v);
            $i++;
        });
        $b->addWatcher('foo', function ($v) use (&$i) {
            self::assertEquals(1, $v);
            $i++;
        });

        $c = $a->mergeWith($b);

        $c->set('foo', 1);

        static::assertEquals(2, $i);
    }
}
