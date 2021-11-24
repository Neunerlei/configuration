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
 * Last modified: 2020.07.05 at 10:50
 */

declare(strict_types=1);


namespace Neunerlei\Configuration\State;


use Neunerlei\Arrays\Arrays;

class ConfigState
{
    /**
     * The internal storage object
     *
     * @var array
     */
    protected $state = [];

    /**
     * The currently set namespace
     *
     * @var string
     */
    protected $namespace;

    /**
     * The list of registered watchers on this state object
     *
     * @var array
     */
    protected $watchers = [];

    /**
     * ConfigState constructor.
     *
     * @param   array  $initialState
     */
    public function __construct(array $initialState)
    {
        $this->state = $initialState;
    }

    /**
     * Returns true if a given key exists, false if not.
     * Note: The method is namespace sensitive!
     *
     * @param   string  $key  A key or path to get the value for
     *
     * @return bool
     */
    public function has(string $key): bool
    {
        return Arrays::hasPath($this->state, $this->getKeyPath($key));
    }

    /**
     * Sets a value in the config state
     * Note: The method is namespace sensitive!
     *
     * @param   string  $key    Either a simple key or a colon separated path to set the value at
     * @param   mixed   $value  The value to set for the given key.
     *                          NOTE: When the state is cached all values MUST be JSON serializable!
     *
     * @return $this
     */
    public function set(string $key, $value): self
    {
        $this->handleWatchers(function () use ($key, $value) {
            $this->state = ArraysWatchable::setPath($this->state, $this->getKeyPath($key), $value);
        });

        return $this;
    }

    /**
     * Works similar to set() but accepts an array of $key => $value pairs to set multiple keys
     * at once.
     *
     * @param   array  $list  A list of $key => $value pairs to set
     *
     * @return $this
     */
    public function setMultiple(array $list): self
    {
        $this->handleWatchers(function () use ($list) {
            foreach ($list as $k => $v) {
                $this->state = ArraysWatchable::setPath($this->state, $this->getKeyPath($k), $v);
            }
        });

        return $this;
    }

    /**
     * Helper to attach the given value to a string key in the state object.
     * If the value is currently no string, the old value will be dropped and replaced with the given value!
     *
     * @param   string  $key    The storage key to store the value at
     * @param   string  $value  The value to add
     * @param   bool    $nl     If set to true a new line will be added before the value.
     *                          If the value is currently empty no new line will be inserted
     *
     * @return $this
     */
    public function attachToString(
        string $key,
        string $value,
        bool $nl = false
    ): self {
        if (empty($value)) {
            return $this;
        }

        $v = $this->get($key, '');

        if (! is_string($v)) {
            $v = '';
        }

        $this->set($key, $v . ($nl && ! empty($v) ? PHP_EOL : '') . $value);

        return $this;
    }

    /**
     * Helper to attach a given value at the end of an array. If the given key is not yet an array,
     * it will be converted into one.
     *
     * @param   string  $key    The storage key to store the value at
     * @param   mixed   $value  The value to add
     *
     * @return $this
     */
    public function attachToArray(string $key, $value): self
    {
        $v = $this->get($key, []);

        if (! is_array($v)) {
            $v = [];
        }

        $v[] = $value;
        $this->set($key, $v);

        return $this;
    }

    /**
     * Helper to merge the given value into an existing array. If the given key is not yet an array,
     * it will be converted into one. Numeric keys will not be overwritten!
     *
     * @param   string  $key    The storage key to store the value at
     * @param   array   $value  The value to merge into the existing array
     *
     * @return $this
     */
    public function mergeIntoArray(string $key, array $value): self
    {
        if (empty($value)) {
            return $this;
        }

        $v = $this->get($key, []);
        if (! is_array($v)) {
            $v = [];
        }
        $v = Arrays::merge($v, $value, 'nn');
        $this->set($key, $v);

        return $this;
    }

    /**
     * Helper to store a given $value as a json encoded value into the state object.
     * This can be helpful if you have a big data object which is only required once or twice in 100 requests,
     * so the cache can handle the value as a string and does not have to rehydrate the data on every request.
     *
     * @param   string  $key              The storage key to store the value at
     * @param   mixed   $value            The value to add
     * @param   bool    $writeEmpty       If this is TRUE empty values are stored into the state, too.
     *                                    Otherwise NULL is written into the state.
     *
     * @return $this
     * @throws \JsonException
     */
    public function setAsJson(string $key, $value, bool $writeEmpty = false): self
    {
        if (empty($value) && ! $writeEmpty) {
            $this->set($key, null);

            return $this;
        }

        $this->set($key, json_encode($value, JSON_THROW_ON_ERROR));

        return $this;
    }

    /**
     * Helper to store a given $value as a serialized string into the state object.
     * This can be helpful if you have a object you want to store in the state that can be serialized
     *
     * @param   string  $key    The storage key to store the value at
     * @param   mixed   $value  The value to add
     *
     * @return $this
     */
    public function setSerialized(string $key, $value): self
    {
        $this->set($key, serialize($value));

        return $this;
    }

    /**
     * Returns the stored value for a given key, or returns the $fallback
     * if the key was not found. Note: The method is namespace sensitive!
     *
     * @param   string      $key       Either a simple key or a colon separated path to find the value at
     * @param   null|mixed  $fallback  Returned if the $key was not found in the state
     *
     * @return mixed|null
     */
    public function get(string $key, $fallback = null)
    {
        return Arrays::getPath($this->state, $this->getKeyPath($key), $fallback);
    }

    /**
     * Allows you to perform multiple actions inside a given namespace.
     * This is useful if you want to write/read multiple entries that are stored in
     * a nested array which would lead to keys like: 'foo.bar.baz.key', 'foo.bar.baz.otherKey' and so on.
     * When you use 'foo.bar.baz' as a namespace you can simply perform the actions on 'key' or 'otherKey'.
     *
     * The method can be called nested
     *
     * @param   null|string  $namespace  The namespace to use, null to break out of the current namespace
     * @param   callable     $callback   The content of the callback will be executed with the given namespace applied.
     *                                   The callback receives $this as parameter.
     *
     * @return $this
     */
    public function useNamespace(?string $namespace, callable $callback): self
    {
        // Update the namespace
        $namespaceBackup = $this->namespace;
        $this->namespace = $namespace;

        // Call the callback and restore the handle after execution
        try {
            $callback($this);
        } finally {
            $this->namespace = $namespaceBackup;
        }

        return $this;
    }

    /**
     * Returns the whole state content as an array
     *
     * @return array
     */
    public function getAll(): array
    {
        return $this->state;
    }

    /**
     * Merges the given $state object into the current state and returns a NEW instance
     * with the combined state of both objects. The merge is performed recursively on arrays.
     * The given $state wins by default if a key exists in both and does not contain an array.
     *
     * @param   \Neunerlei\Configuration\State\ConfigState  $state  The state to be merged into this state object
     *
     * @return \Neunerlei\Configuration\State\ConfigState
     */
    public function mergeWith(ConfigState $state): self
    {
        $clone = clone $this;

        return $clone->importFrom($state);
    }

    /**
     * Quite similar to mergeWith() but imports the state of the given configuration into THIS instance,
     * instead of creating a new instance.
     *
     * @param   \Neunerlei\Configuration\State\ConfigState  $state
     *
     * @return $this
     */
    public function importFrom(ConfigState $state): self
    {
        $this->watchers = Arrays::merge($this->watchers, $state->watchers, 'nn');
        $this->setMultiple(Arrays::flatten($state->getAll()));

        return $this;
    }

    /**
     * Adds a new watcher for a storage key. Watchers will be executed every time
     * their respective key or one of their children gets updated using the set() methods.
     *
     * This means you can get notified if a configuration state changes.
     * For example: You register a watcher on the key: "foo.bar"
     *
     * Now you set a value to $state->set('foo', ['test' => 123]);
     * Your watcher will NOT be triggered in that case
     * However, if you set another value to $state->set('foo', ['bar' => 123]);
     * your watcher will be triggered, because foo.bar got updated.
     * It will also work if you set $state->set('foo.bar', 123) directly.
     *
     * The watcher will also get notified if you set one of its children.
     * This means if you set something like $state->set('foo.bar.baz', 123), the watcher will trigger,
     * because it listens to deep changes.
     *
     * NOTE: Only simple paths are supported as watcher keys, meaning stuff like foo.bar[foo,bar] or foo.*.bar
     * will NOT work!
     *
     * @param   string    $key      The selector of the property to watch
     * @param   callable  $watcher  The callback to execute if the property changed
     *
     * @return $this
     */
    public function addWatcher(string $key, callable $watcher): self
    {
        $this->watchers[implode('.', $this->getKeyPath($key))][] = $watcher;

        return $this;
    }

    /**
     * Removes a previously registered watcher from the list.
     *
     * @param   callable  $watcher  The callback to remove from the list of watchers
     *
     * @return $this
     */
    public function removeWatcher(callable $watcher): self
    {
        foreach ($this->watchers as $key => $watcherList) {
            $this->watchers[$key] = array_filter($watcherList, static function ($w) use ($watcher) {
                return $watcher !== $w;
            });
            if (empty($this->watchers[$key])) {
                unset($this->watchers[$key]);
            }
        }

        return $this;
    }

    /**
     * Internal helper to keep track of the changed properties and notify their respective watchers
     *
     * @param   callable  $callback  The setter actions that should be watched while being executed
     */
    protected function handleWatchers(callable $callback): void
    {
        // Fastlane if we don't have watchers
        if (empty($this->watchers)) {
            $callback();
        }

        // Collect the list of watchers and notify them
        try {
            ArraysWatchable::$keysToTrigger = [];

            $callback();

            $keysToTrigger = array_keys(ArraysWatchable::$keysToTrigger);

            foreach ($keysToTrigger as $key) {
                if (empty($this->watchers[$key])) {
                    continue;
                }
                $value = Arrays::getPath($this->state, $key);
                foreach ($this->watchers[$key] as $watcher) {
                    $watcher($value);
                }
            }

        } finally {
            ArraysWatchable::$keysToTrigger = [];
        }
    }

    /**
     * Internal helper to split a key into a list of parts, including the currently set namespace
     *
     * @param   string  $key  The key to split up
     *
     * @return array
     */
    protected function getKeyPath(string $key): array
    {
        return Arrays::mergePaths((string)$this->namespace, $key);
    }
}
