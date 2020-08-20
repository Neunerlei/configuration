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
 * Last modified: 2020.07.13 at 15:45
 */

declare(strict_types=1);


namespace Neunerlei\ConfigTests;


use Closure;
use ReflectionObject;

trait TestHelperTrait
{
    
    /**
     * Helper to create a caller for protected methods
     *
     * @param   object  $object
     * @param   string  $method
     *
     * @return \Closure
     */
    protected function makeCaller(object $object, string $method): Closure
    {
        $ref = new ReflectionObject($object);
        $m   = $ref->getMethod($method);
        $m->setAccessible(true);
        
        return function (...$args) use ($m, $object) {
            return $m->invokeArgs($object, $args);
        };
    }
    
    /**
     * Helper to extract a value out of a protected property
     *
     * @param   object  $object
     * @param   string  $property
     *
     * @return mixed
     */
    protected function getValue(object $object, string $property)
    {
        $ref  = new ReflectionObject($object);
        $prop = $ref->getProperty($property);
        $prop->setAccessible(true);
        
        return $prop->getValue($object);
    }
}
