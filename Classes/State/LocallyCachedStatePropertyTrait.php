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
 * Last modified: 2020.09.04 at 11:19
 */

declare(strict_types=1);


namespace Neunerlei\Configuration\State;


use InvalidArgumentException;

trait LocallyCachedStatePropertyTrait
{

    /**
     * Allows you to keep a property of the current class in sync with a key in the config state object.
     * This allows local caches in classes that have to read specific config states hundreds or thousands of times
     * while the script is executed.
     *
     * This method should be called once per object creation presumably in the constructor.
     *
     * @param   string         $propertyName  The name of the property on the local object to keep in sync with the
     *                                        config state
     * @param   string         $configKey     The key of the configuration value to keep in sync
     * @param   ConfigState    $configState   The config state to sync the property with
     * @param   callable|null  $filter        Optional filter callback that is executed before the updated
     *                                        value will be written into the local property. This allows
     *                                        unpacking of serialized values on the fly, a "cheap" change listener,
     *                                        or last-minute value modification. The callback receives the value
     *                                        an must return the filtered value.
     * @param   mixed          $fallback      Set as a default value if the $configKey was not found in the state
     *
     * @see \Neunerlei\Configuration\State\ConfigState::addWatcher()
     */
    protected function registerCachedProperty(
        string $propertyName,
        string $configKey,
        ConfigState $configState,
        ?callable $filter = null,
        $fallback = null
    ): void {
        if (! property_exists($this, $propertyName)) {
            throw new InvalidArgumentException(
                'The given property: "' . $propertyName . '" does not exist in class: "'
                . static::class . '"!');
        }

        $v                     = $configState->get($configKey, $fallback);
        $this->{$propertyName} = $filter !== null ? call_user_func($filter, $v) : $v;

        $configState->addWatcher($configKey, function ($v) use ($propertyName, $filter) {
            $this->{$propertyName} = $filter !== null ? call_user_func($filter, $v) : $v;
        });
    }
}
