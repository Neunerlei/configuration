<?php
/*
 * Copyright 2021 LABOR.digital
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
 * Last modified: 2021.02.12 at 14:08
 */

declare(strict_types=1);


namespace Neunerlei\Configuration\State;


use Neunerlei\Arrays\Arrays;
use Neunerlei\Arrays\Traits\PathTrait;

class ArraysWatchable extends Arrays
{

    /**
     * Holds the list of all collected keys we should trigger our watcher for
     *
     * @var array
     */
    public static $keysToTrigger = [];

    /**
     * Internal helper to hold the currently set key path
     *
     * @var array
     */
    protected static $currentKey = [];

    /**
     * @inheritDoc
     */
    protected static function initWalkerStep(array $input, array &$path): array
    {
        $result = parent::initWalkerStep($input, $path);
        [$keys] = $result;
        static::$currentKey[] = reset($keys);

        // We add each step to the key list, so we can build the whole tree key
        // with all steps that might be involved
        static::$keysToTrigger[implode('.', static::$currentKey)] = true;

        return $result;
    }

    /**
     * @inheritDoc
     */
    protected static function setPathWalker(array &$list, array $path, $value): void
    {
        // Special handling for setting array values,
        // because we have to take their structure into account when generating the list
        // of keys to trigger
        if (empty(static::$currentKey) && is_array($value)) {
            parent::setPathWalker($list, $path, $value);

            $rootKey = implode('.', static::$currentKey);
            foreach (array_keys(Arrays::flatten($value)) as $subKey) {
                static::$keysToTrigger[$rootKey . '.' . $subKey] = true;
            }
        }

        parent::setPathWalker($list, $path, $value);
    }

    /**
     * @inheritDoc
     */
    protected static function canUseFastLane($path, string $separator): bool
    {
        static::$currentKey = [];

        return false;
    }


}
