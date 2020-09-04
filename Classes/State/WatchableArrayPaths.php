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
 * Last modified: 2020.09.03 at 22:48
 */

declare(strict_types=1);


namespace Neunerlei\Configuration\State;


use Neunerlei\Arrays\ArrayPaths;
use Neunerlei\Arrays\Arrays;

/**
 * Class WatchableArrayPaths
 *
 * A hook into the array paths class to keep track of which paths are getting set in the state.
 * We use this information to determine which watchers we should trigger
 *
 * @package Neunerlei\Configuration\State
 */
class WatchableArrayPaths extends ArrayPaths
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
    protected $currentKey = [];

    /**
     * @inheritDoc
     */
    protected function initWalkerStep(array $input, array &$path): array
    {
        $result = parent::initWalkerStep($input, $path);
        [$keys] = $result;
        $this->currentKey[] = reset($keys);

        // We add each step to the key list, so we can build the whole tree key
        // with all steps that might be involved
        static::$keysToTrigger[implode('.', $this->currentKey)] = true;

        return $result;
    }

    /**
     * @inheritDoc
     */
    protected function setWalker(array &$list, array $path, $value): void
    {
        // Special handling for setting array values,
        // because we have to take their structure into account when generating the list
        // of keys to trigger
        if (empty($this->currentKey) && is_array($value)) {
            parent::setWalker($list, $path, $value);

            $rootKey = implode('.', $this->currentKey);
            foreach (array_keys(Arrays::flatten($value)) as $subKey) {
                static::$keysToTrigger[$rootKey . '.' . $subKey] = true;
            }
        }

        parent::setWalker($list, $path, $value);
    }

    /**
     * @inheritDoc
     */
    protected function canUseFastLane($path, string $separator): bool
    {
        $this->currentKey = [];

        return false;
    }


}
