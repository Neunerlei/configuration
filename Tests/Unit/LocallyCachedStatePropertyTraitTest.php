<?php
/*
 * Copyright 2020 LABOR.digital
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
 * Last modified: 2020.09.04 at 11:25
 */

declare(strict_types=1);


namespace Neunerlei\ConfigTests\Unit;


use Neunerlei\Configuration\State\ConfigState;
use Neunerlei\Configuration\State\LocallyCachedStatePropertyTrait;
use PHPUnit\Framework\TestCase;

class LocallyCachedStatePropertyTraitTest extends TestCase
{

    public function testSync()
    {
        $state = new ConfigState([]);
        $mock  = new class($state) {
            use LocallyCachedStatePropertyTrait;

            public $property;

            public function __construct(ConfigState $state)
            {
                $this->registerCachedProperty('property', 'test.property', $state);
            }
        };

        self::assertNull($mock->property);
        $state->set('test', ['foo' => 123]);
        self::assertNull($mock->property);
        $state->set('test.property', ['bar' => ['baz' => 123]]);
        self::assertEquals(['bar' => ['baz' => 123]], $mock->property);
        $state->set('test.property.bar.foo', 234);
        $state->set('test.property.foo', 1);
        self::assertEquals(['bar' => ['baz' => 123, 'foo' => 234], 'foo' => 1], $mock->property);

    }

    public function testInvalidPropertyNameException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('~^The given property: "notExisting" does not exist in class: ~');
        new class {
            use LocallyCachedStatePropertyTrait;

            public function __construct()
            {
                $this->registerCachedProperty('notExisting', 'test.property', new ConfigState([]));
            }
        };
    }

    public function testFilterAndDefault()
    {
        $filter = function ($v) {
            return is_string($v) ? $v . '.filtered' : $v;
        };

        $state = new ConfigState([]);
        $mock  = new class($state, $filter) {
            use LocallyCachedStatePropertyTrait;

            public $property;
            public $propertyDefault;

            public function __construct(ConfigState $state, callable $filter)
            {
                $this->registerCachedProperty('property', 'value', $state, $filter);
                $this->registerCachedProperty('propertyDefault', 'value', $state, $filter, 'default');
            }
        };

        static::assertNull($mock->property);
        static::assertEquals('default.filtered', $mock->propertyDefault);

        $state->set('value', 'foo');

        static::assertEquals('foo.filtered', $mock->property);
        static::assertEquals('foo.filtered', $mock->propertyDefault);
    }
}
