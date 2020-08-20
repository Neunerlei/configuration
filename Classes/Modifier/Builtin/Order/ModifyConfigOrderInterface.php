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
 * Last modified: 2020.07.07 at 17:43
 */

declare(strict_types=1);


namespace Neunerlei\Configuration\Modifier\Builtin\Order;


interface ModifyConfigOrderInterface
{
    /**
     * Allows you to define the order of this configuration in relation to other configuration classes.
     *
     * If you want to execute this config class after let's say "\FooConfig" simply add it to the $executeMeAfter
     * array like $executeMeAfter[] = \FooConfig::class and you are set.
     *
     * @param   array  $executeMeBefore  The list of other config classes that should be executed AFTER this one
     * @param   array  $executeMeAfter   The list of other config classes that should be executed BEFORE this one
     */
    public static function setConfigOrder(array &$executeMeBefore, array &$executeMeAfter): void;
}
