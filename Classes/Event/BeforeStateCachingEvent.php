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
 * Last modified: 2021.02.15 at 20:27
 */

declare(strict_types=1);


namespace Neunerlei\Configuration\Event;


use Neunerlei\Configuration\Loader\Loader;
use Neunerlei\Configuration\Loader\LoaderContext;
use Neunerlei\Configuration\State\ConfigState;

class BeforeStateCachingEvent
{
    /**
     * True if a cache implementation exists, false if not
     *
     * @var bool
     */
    protected $hasCache;

    /**
     * Contains the cache key that is uses to store the state
     *
     * @var string
     */
    protected $cacheKey;

    /**
     * The context for the loader instance itself
     *
     * @var \Neunerlei\Configuration\Loader\LoaderContext
     */
    protected $loaderContext;

    /**
     * The loader instance this event is emitted for
     *
     * @var \Neunerlei\Configuration\Loader\Loader
     */
    protected $loader;

    /**
     * BeforeConfigLoadEvent constructor.
     *
     * @param   bool                                           $hasCache
     * @param   string                                         $cacheKey
     * @param   \Neunerlei\Configuration\Loader\LoaderContext  $loaderContext
     * @param   \Neunerlei\Configuration\Loader\Loader         $loader
     */
    public function __construct(
        bool $hasCache,
        string $cacheKey,
        LoaderContext $loaderContext,
        Loader $loader
    ) {
        $this->hasCache      = $hasCache;
        $this->cacheKey      = $cacheKey;
        $this->loaderContext = $loaderContext;
        $this->loader        = $loader;
    }

    /**
     * Returns the configured state object that is about to be cached
     *
     * @return \Neunerlei\Configuration\State\ConfigState
     */
    public function getState(): ConfigState
    {
        return $this->loaderContext->configContext->getState();
    }

    /**
     * Returns true if a cache implementation exists, false if not
     *
     * @return bool
     */
    public function hasCache(): bool
    {
        return $this->hasCache;
    }

    /**
     * Returns the cache key that is uses to store the state
     *
     * @return string
     */
    public function getCacheKey(): string
    {
        return $this->cacheKey;
    }

    /**
     * Returns the context for the loader instance
     *
     * @return \Neunerlei\Configuration\Loader\LoaderContext
     */
    public function getLoaderContext(): LoaderContext
    {
        return $this->loaderContext;
    }

    /**
     * Returns the loader instance this event is emitted for
     *
     * @return \Neunerlei\Configuration\Loader\Loader
     */
    public function getLoader(): Loader
    {
        return $this->loader;
    }
}
