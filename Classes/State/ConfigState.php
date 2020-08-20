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


use Closure;

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
        $fallback = 'false-' . microtime(true);
        
        return $this->get($key, $fallback) !== $fallback;
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
        $path = $this->getPathParts($key);
        $temp =& $this->state;
        
        foreach ($path as $k) {
            if (isset($temp[$k]) && ! is_array($temp[$k])) {
                $temp[$k] = [];
            }
            $temp =& $temp[$k];
        }
        $temp = $value;
        
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
        foreach ($list as $k => $v) {
            $this->set($k, $v);
        }
        
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
        $path = $this->getPathParts($key);
        $temp =& $this->state;
        
        foreach ($path as $k) {
            if (! isset($temp[$k])) {
                return $fallback;
            }
            $temp =& $temp[$k];
        }
        
        return $temp;
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
    public function mergeWith(ConfigState $state): ConfigState
    {
        // Recursive array merger
        $merger = static function (array $a, array $b, Closure $merger): array {
            $c = $a;
            foreach ($b as $k => $v) {
                if (is_array($v) && isset($a[$k]) && is_array($a[$k])) {
                    $v = $merger($a[$k], $v, $merger);
                }
                $c[$k] = $v;
            }
            
            return $c;
        };
        
        // Merge the states recursively into a single state
        $clone        = clone $this;
        $clone->state = $merger($clone->getAll(), $state->getAll(), $merger);
        
        return $clone;
    }
    
    /**
     * Internal helper to split a key into a list of parts, including the currently set namespace
     *
     * @param   string  $key  The key to split up
     *
     * @return array
     */
    protected function getPathParts(string $key): array
    {
        return array_filter(array_merge([], explode('.', (string)$this->namespace), explode('.', $key)));
    }
}
