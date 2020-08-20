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
 * Last modified: 2020.07.07 at 17:20
 */

declare(strict_types=1);


namespace Neunerlei\Configuration\Modifier;


interface ConfigModifierInterface
{
    
    /**
     * Must return a unique key for this modifier.
     * By default you should use return __CLASS__; as body for this function.
     * It is used to allow modifiers to be overwritten by other modifiers.
     *
     * @return string
     */
    public function getKey(): string;
    
    /**
     * Applies the modifier to the given context object.
     *
     * @param   \Neunerlei\Configuration\Modifier\ModifierContext  $context
     */
    public function apply(ModifierContext $context): void;
}
