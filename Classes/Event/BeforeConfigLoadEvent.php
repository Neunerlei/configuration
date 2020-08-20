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
 * Last modified: 2020.07.07 at 18:42
 */

declare(strict_types=1);


namespace Neunerlei\Configuration\Event;


use Neunerlei\Configuration\Loader\Loader;
use Neunerlei\Configuration\Loader\LoaderContext;

class BeforeConfigLoadEvent
{
    /**
     * True if the config is loaded as a runtime config
     *
     * @var bool
     */
    protected $isRuntime;
    
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
     * @param   bool                                           $isRuntime
     * @param   \Neunerlei\Configuration\Loader\LoaderContext  $loaderContext
     * @param   \Neunerlei\Configuration\Loader\Loader         $loader
     */
    public function __construct(
        bool $isRuntime,
        LoaderContext $loaderContext,
        Loader $loader
    ) {
        $this->isRuntime     = $isRuntime;
        $this->loaderContext = $loaderContext;
        $this->loader        = $loader;
    }
    
    /**
     * Returns true if the config is loaded as a runtime config
     *
     * @return bool
     */
    public function isRuntime(): bool
    {
        return $this->isRuntime;
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
     * Updates the context for the loader instance
     *
     * @param   \Neunerlei\Configuration\Loader\LoaderContext  $loaderContext
     */
    public function setLoaderContext(LoaderContext $loaderContext): void
    {
        $this->loaderContext = $loaderContext;
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
