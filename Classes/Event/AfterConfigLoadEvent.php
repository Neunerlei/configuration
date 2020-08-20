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
 * Last modified: 2020.07.07 at 18:55
 */

declare(strict_types=1);


namespace Neunerlei\Configuration\Event;


use Neunerlei\Configuration\Loader\LoaderContext;
use Neunerlei\Configuration\State\ConfigState;

/**
 * Class AfterConfigLoadEvent
 *
 * Allows you to filter the generated state after it was generated
 *
 * @package Neunerlei\Configuration\Event
 */
class AfterConfigLoadEvent
{
    
    /**
     * True if the config is loaded as a runtime config
     *
     * @var bool
     */
    protected $isRuntime;
    
    /**
     * True if the given state has been generated based on cached data
     *
     * @var bool
     */
    protected $isCached;
    
    /**
     * The context object the config is generated for
     *
     * @var \Neunerlei\Configuration\Loader\LoaderContext
     */
    protected $loaderContext;
    
    /**
     * The generated state object to alter
     *
     * @var \Neunerlei\Configuration\State\ConfigState
     */
    protected $state;
    
    /**
     * AfterConfigLoadEvent constructor.
     *
     * @param   bool                                           $isRuntime
     * @param   bool                                           $isCached
     * @param   \Neunerlei\Configuration\Loader\LoaderContext  $loaderContext
     * @param   \Neunerlei\Configuration\State\ConfigState     $state
     */
    public function __construct(bool $isRuntime, bool $isCached, LoaderContext $loaderContext, ConfigState $state)
    {
        $this->isRuntime     = $isRuntime;
        $this->isCached      = $isCached;
        $this->loaderContext = $loaderContext;
        $this->state         = $state;
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
     * Returns true if the given state has been generated based on cached data
     *
     * @return bool
     */
    public function isCached(): bool
    {
        return $this->isCached;
    }
    
    /**
     * Returns the context object the config is generated for
     *
     * @return \Neunerlei\Configuration\Loader\LoaderContext
     */
    public function getLoaderContext(): LoaderContext
    {
        return $this->loaderContext;
    }
    
    /**
     * Returns the generated state object to alter
     *
     * @return \Neunerlei\Configuration\State\ConfigState
     */
    public function getState(): ConfigState
    {
        return $this->state;
    }
}
