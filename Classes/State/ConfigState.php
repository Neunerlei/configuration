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
            $this->state = Arrays::setPath($this->state, $this->getKeyPath($key), $value);
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
                $this->state = Arrays::setPath($this->state, $this->getKeyPath($k), $v);
            }
        });

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
     * @throws \Neunerlei\Arrays\ArrayException
     */
    public function mergeWith(ConfigState $state): ConfigState
    {
        $clone           = clone $this;
        $clone->state    = Arrays::merge($clone->getAll(), $state->getAll());
        $clone->watchers = Arrays::merge($clone->watchers, $state->watchers);

        return $clone;
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
        $pathClassBackup = Arrays::$pathClass;
        try {
            WatchableArrayPaths::$keysToTrigger = [];
            Arrays::$pathClass                  = WatchableArrayPaths::class;

            $callback();

            $keysToTrigger                      = array_keys(WatchableArrayPaths::$keysToTrigger);
            Arrays::$pathClass                  = $pathClassBackup;
            WatchableArrayPaths::$keysToTrigger = [];

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
            Arrays::$pathClass                  = $pathClassBackup;
            WatchableArrayPaths::$keysToTrigger = [];
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
