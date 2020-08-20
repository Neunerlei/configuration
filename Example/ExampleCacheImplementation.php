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
 * Last modified: 2020.07.08 at 11:49
 */

declare(strict_types=1);


namespace Neunerlei\ConfigExample;


use Psr\SimpleCache\CacheInterface;

/**
 * Class ExampleCacheImplementation
 *
 * Super-simple cache implementation to show how it works
 *
 * @package Neunerlei\ConfigExample
 */
class ExampleCacheImplementation implements CacheInterface
{
    /**
     * Simple runtime cache
     *
     * @var array
     */
    protected $cache = [];
    
    /**
     * @var bool
     */
    protected $verbose;
    
    /**
     * ExampleCacheImplementation constructor.
     *
     * @param   bool  $verbose
     */
    public function __construct(bool $verbose = true)
    {
        $this->verbose = $verbose;
    }
    
    /**
     * @inheritDoc
     */
    public function get($key, $default = null)
    {
        if ($this->verbose) {
            echo PHP_EOL;
            echo 'Read a value with key: ' . $key . PHP_EOL;
            print_r($this->cache[$key] ?? $default);
            echo PHP_EOL;
        }
        
        return $this->cache[$key] ?? $default;
    }
    
    /**
     * @inheritDoc
     */
    public function set($key, $value, $ttl = null)
    {
        if ($this->verbose) {
            echo PHP_EOL;
            echo 'Writing a value with key: ' . $key . PHP_EOL;
            print_r($value);
            echo PHP_EOL;
        }
        $this->cache[$key] = $value;
        
        return true;
    }
    
    /**
     * @inheritDoc
     */
    public function delete($key)
    {
        unset($this->cache[$key]);
        
        return true;
    }
    
    /**
     * @inheritDoc
     */
    public function clear()
    {
        $this->cache = [];
        
        return true;
    }
    
    /**
     * @inheritDoc
     */
    public function getMultiple($keys, $default = null)
    {
        $r = [];
        foreach ($keys as $key) {
            $r[$key] = $this->get($key, $default);
        }
        
        return $r;
    }
    
    /**
     * @inheritDoc
     */
    public function setMultiple($values, $ttl = null)
    {
        foreach ($values as $k => $v) {
            $this->set($k, $v);
        }
        
        return true;
    }
    
    /**
     * @inheritDoc
     */
    public function deleteMultiple($keys)
    {
        foreach ($keys as $key) {
            $this->delete($key);
        }
        
        return true;
    }
    
    /**
     * @inheritDoc
     */
    public function has($key)
    {
        return isset($this->cache[$key]);
    }
    
    /**
     * Returns all stored data in the cache
     *
     * @return array
     */
    public function getAll(): array
    {
        return $this->cache;
    }
}
