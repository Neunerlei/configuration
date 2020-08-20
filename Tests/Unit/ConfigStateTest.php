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
        $this->assertEquals($state, $i->getAll());
    }
    
    public function testSet(): void
    {
        $i = new ConfigState([]);
        $this->assertEquals([], $i->getAll());
        
        $i->set('foo', 'bar');
        $this->assertEquals(['foo' => 'bar'], $i->getAll());
        
        $i->set('foo', 123);
        $this->assertEquals(['foo' => 123], $i->getAll());
        
        $i->set('foo.bar', 123);
        $this->assertEquals(['foo' => ['bar' => 123]], $i->getAll());
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
        $this->assertEquals([
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
        $this->assertTrue($i->get('foo'));
        $this->assertFalse($i->get('bar'));
        $this->assertEquals(123, $i->get('baz'));
        $this->assertEquals(['foo' => 'asdf'], $i->get('faz'));
        $this->assertEquals('asdf', $i->get('faz.foo'));
        $this->assertNull($i->get('notExistent'));
        $this->assertNull($i->get('notExistent.too'));
        $this->assertTrue($i->get('notExistent', true));
        $this->assertTrue($i->get('foo', false));
    }
    
    public function testHas(): void
    {
        $i = new ConfigState([]);
        $this->assertFalse($i->has('foo'));
        $this->assertFalse($i->has('bar'));
        $i->set('foo', 123);
        $this->assertTrue($i->has('foo'));
        $this->assertFalse($i->has('bar'));
        $i->set('bar.baz.foo', 123);
        $this->assertTrue($i->has('bar'));
        $this->assertTrue($i->has('bar.baz'));
        $this->assertTrue($i->has('bar.baz.foo'));
    }
    
    public function testUseNamespace()
    {
        $i = new ConfigState([]);
        
        // Simple namespace
        $i->useNamespace('foo', function (ConfigState $i) {
            $this->assertInstanceOf(ConfigState::class, $i);
            $i->set('foo', 123);
            $i->set('bar', true);
            $this->assertTrue($i->get('bar'));
            $this->assertTrue($i->has('foo'));
            $this->assertEquals(['foo' => ['foo' => 123, 'bar' => true]], $i->getAll());
        });
        $this->assertEquals(['foo' => ['foo' => 123, 'bar' => true]], $i->getAll());
        
        // Nested namespace
        $i->useNamespace('foo', function () use ($i) {
            // We are back in the previous namespace
            $this->assertTrue($i->get('bar'));
            $this->assertTrue($i->has('foo'));
            $i->set('parent', true);
            
            $i->useNamespace('baz', function () use ($i) {
                // Values from the parent namespace
                $this->assertNull($i->get('bar'));
                $this->assertFalse($i->has('parent'));
                $this->assertFalse($i->has('foo'));
                
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
                    $this->assertTrue($i->has('foo'));
                    $this->assertTrue($i->get('foo.bar'));
                    $this->assertEquals(123, $i->get('foo.foo'));
                    
                    // Nested sub space
                    $this->assertTrue($i->has('foo.sub.space'));
                    $this->assertTrue($i->get('foo.sub.space.test'));
                    $this->assertEquals('asdf', $i->get('foo.sub.space.test2'));
                    
                    // "baz" namespace
                    $this->assertTrue($i->has('baz'));
                    $this->assertEquals(456, $i->get('baz.foo'));
                    $this->assertEquals(789, $i->get('baz.bar'));
                    
                });
            });
            
            // We should now be back in the parent namespace
            $this->assertTrue($i->has('parent'));
        });
        
        // Now let's check our result
        $this->assertEquals([
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
        $this->assertNotSame($a, $c);
        $this->assertNotSame($b, $c);
        $this->assertEquals([
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
}
