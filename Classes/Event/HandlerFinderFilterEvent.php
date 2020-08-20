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
 * Last modified: 2020.07.13 at 11:39
 */

declare(strict_types=1);


namespace Neunerlei\Configuration\Event;


use Neunerlei\Configuration\Finder\HandlerFinderInterface;
use Neunerlei\Configuration\Loader\LoaderContext;

class HandlerFinderFilterEvent
{
    /**
     * The implementation that finds handler classes in the registered locations
     *
     * @var \Neunerlei\Configuration\Finder\HandlerFinderInterface
     */
    protected $handlerFinder;
    
    /**
     * The context for the loader instance itself
     *
     * @var \Neunerlei\Configuration\Loader\LoaderContext
     */
    protected $loaderContext;
    
    /**
     * HandlerFinderFilterEvent constructor.
     *
     * @param   \Neunerlei\Configuration\Finder\HandlerFinderInterface  $handlerFinder
     * @param   \Neunerlei\Configuration\Loader\LoaderContext           $loaderContext
     */
    public function __construct(HandlerFinderInterface $handlerFinder, LoaderContext $loaderContext)
    {
        $this->handlerFinder = $handlerFinder;
        $this->loaderContext = $loaderContext;
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
     * Returns the implementation that finds handler classes in the registered locations
     *
     * @return \Neunerlei\Configuration\Finder\HandlerFinderInterface
     */
    public function getHandlerFinder(): HandlerFinderInterface
    {
        return $this->handlerFinder;
    }
    
    /**
     * Updates the implementation that finds handler classes in the registered locations
     *
     * @param   \Neunerlei\Configuration\Finder\HandlerFinderInterface  $handlerFinder
     */
    public function setHandlerFinder(HandlerFinderInterface $handlerFinder): void
    {
        $this->handlerFinder = $handlerFinder;
    }
    
}
