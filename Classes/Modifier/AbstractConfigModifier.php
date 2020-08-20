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
 * Last modified: 2020.07.07 at 17:38
 */

declare(strict_types=1);


namespace Neunerlei\Configuration\Modifier;


use InvalidArgumentException;

abstract class AbstractConfigModifier implements ConfigModifierInterface
{
    /**
     * @inheritDoc
     */
    public function getKey(): string
    {
        return get_called_class();
    }
    
    /**
     * Filters a given list of classes and only keeps the classes that implement
     * the given $searchInterface or interfaces if an array is given.
     *
     * @param   array         $classes          The list of classes to filter
     * @param   array|string  $searchInterface  Either a single interface as string, or a list of interfaces
     *                                          to filter the given class list by
     *
     * @return array The filtered $classes with only items that implement one or more of the given $searchInterface
     */
    protected function findClassesWithInterface(array $classes, $searchInterface): array
    {
        if (is_string($searchInterface)) {
            $searchInterface = [$searchInterface];
        }
        if (! is_array($searchInterface)) {
            throw new InvalidArgumentException('$interface must either be an array or a string!');
        }
        
        return array_filter($classes, static function (string $class) use ($searchInterface): bool {
            $interfaces = class_implements($class);
            
            return ! empty(array_intersect($interfaces, $searchInterface));
        });
    }
}
