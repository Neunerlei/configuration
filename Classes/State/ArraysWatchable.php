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

class ArraysWatchable extends Arrays
{
    protected const MOPT_ALLOW_REMOVAL        = 0;
    protected const MOPT_NUMERIC_MERGE        = 1;
    protected const MOPT_STRICT_NUMERIC_MERGE = 2;

    protected const MERGE_OPTIONS
        = [
            // type => longKey, shortKey, defaultSetting
            self::MOPT_NUMERIC_MERGE        => ['numericMerge', 'nm', false],
            self::MOPT_STRICT_NUMERIC_MERGE => ['strictNumericMerge', 'sn', false],
            self::MOPT_ALLOW_REMOVAL        => ['allowRemoval', 'r', true],
        ];

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
     * Special handler to merge two state arrays while collecting changes on them
     *
     * NOTE: It is possible to remove keys from an array while they are merged, by using the __UNSET special value.
     * Keep in mind, that the "allowRemoval" flag has to be enabled for that.
     *
     * @param   array  $a        The list to merge $b into
     * @param   array  $b        The list to merge into $a
     * @param   array  $options  A list of options that define the merge strategy
     *                           each option can be either BOOL for a global setting, or an array of state paths and
     *                           their matching option like: ['foo.bar.baz' => true, 'foo.*.foo' => false, ...].
     *                           the "*" is used as a wildcard, when defining paths. All options have "short-keys" to
     *                           save on typing:
     *                           - numericMerge|nm (FALSE): By default, array values of $b, with numeric keys will be
     *                           appended to the array in $a, in the same way array_merge() works. To enable the
     *                           overriding of values with numeric keys set this to true.
     *                           - strictNumericMerge|sn (FALSE): If the "numericMerge" is true, only arrays with
     *                           numeric keys are merged into each other. By setting this flag, values of ALL types
     *                           with the same numeric key will get overwritten by the value in $b.
     *                           - allowRemoval|r (TRUE): If true, the value "__UNSET" feature, which can be used in
     *                           order to unset array keys in the original array, will be disabled.
     *
     * @return array
     */
    public static function mergeStates(array $a, array $b, array $options = []): array
    {
        $config = [];
        foreach (static::MERGE_OPTIONS as $type => [$k, $shortK, $default]) {
            $v = $options[$k]
                 ?? $options[$shortK]
                    ?? (in_array($k, $options, true) || in_array($shortK, $options, true) ? true : $default);

            $v = is_bool($v) || is_array($v) ? $v : $default;

            // We transform the paths to regular expressions in order to look dynamic paths up quickly
            if (is_array($v)) {
                $patterns = [];
                foreach ($v as $_k => $_v) {
                    $patterns[(int)$_v][] = str_replace('\*', '(.*?)', preg_quote((string)$_k, '~'));
                }

                $v = array_map(static function (array $patterns) {
                    return '~^(' . implode('|', $patterns) . ')$~';
                }, $patterns);
                unset($patterns, $_k, $_v);
            }

            $config[$type] = $v;
        }

        return static::mergeStateWalker($a, $b, [], $config);
    }

    /**
     * Internal walker to simulate a merge that handles in the same way Arrays::merge($a, $b, 'nn') does.
     * Changes will be tracked in the $keysToTrigger
     *
     * @param   array  $a
     * @param   array  $b
     * @param   array  $path
     *
     * @return array
     */
    protected static function mergeStateWalker(array $a, array $b, array $path, array $config): array
    {
        $o          = $a;
        $configPath = implode('.', $path);

        foreach ($b as $k => $v) {
            $_path   = $path;
            $_path[] = $k;

            static::$keysToTrigger[implode('.', $_path)] = true;

            if ($v === '__UNSET' && static::mergeStateConfig($config, static::MOPT_ALLOW_REMOVAL, $configPath)) {
                unset($o[$k]);
                continue;
            }

            $vIsArray = is_array($v);
            if (is_numeric($k)
                && (
                    (
                        ! $vIsArray
                        && ! static::mergeStateConfig($config, static::MOPT_STRICT_NUMERIC_MERGE, $configPath)
                    )
                    || ! static::mergeStateConfig($config, static::MOPT_NUMERIC_MERGE, $configPath)
                )
            ) {
                $o[] = $v;
                continue;
            }

            if ($vIsArray) {
                $o[$k] = static::mergeStateWalker(
                    is_array($a[$k] ?? null) ? $a[$k] : [],
                    $v,
                    $_path,
                    $config
                );
                continue;
            }

            $o[$k] = $v;
        }

        return $o;
    }

    /**
     * Internal helper to if a certain config option ($type) was enabled either globally or by the given path
     *
     * @param   array   $config  The prepared config array
     * @param   int     $type    The type to validate for the path
     * @param   string  $path    The current state path to validate the rule for
     *
     * @return bool
     * @see mergeStates()
     */
    protected static function mergeStateConfig(array $config, int $type, string $path): bool
    {
        if (empty($config[$type])) {
            return false;
        }

        if (is_bool($config[$type])) {
            return $config[$type];
        }

        foreach ($config[$type] as $res => $pattern) {
            if (preg_match($pattern, $path)) {
                return (bool)$res;
            }
        }

        return static::MERGE_OPTIONS[$type][2] ?? false;
    }

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
