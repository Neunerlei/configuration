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
 * Last modified: 2020.07.07 at 18:48
 */

declare(strict_types=1);


namespace Neunerlei\Configuration\Event;


use Neunerlei\Configuration\Loader\LoaderContext;

class ConfigHandlerFilterEvent
{
    /**
     * The context object the config is generated for
     *
     * @var \Neunerlei\Configuration\Loader\LoaderContext
     */
    protected $loaderContext;
    
    /**
     * The prepared list of handler configurations to filter
     *
     * @var \Neunerlei\Configuration\Handler\HandlerDefinition[]
     */
    protected $handlers;
    
    /**
     * ConfigHandlerFilterEvent constructor.
     *
     * @param   \Neunerlei\Configuration\Loader\LoaderContext  $loaderContext
     * @param   array                                          $handlers
     */
    public function __construct(LoaderContext $loaderContext, array $handlers)
    {
        $this->loaderContext = $loaderContext;
        $this->handlers      = $handlers;
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
     * Returns the prepared list of handler configurations to filter
     *
     * @return \Neunerlei\Configuration\Handler\HandlerDefinition[]
     */
    public function getHandlers(): array
    {
        return $this->handlers;
    }
    
    /**
     * Updates the prepared list of handler configurations to filter
     *
     * @param   \Neunerlei\Configuration\Handler\HandlerDefinition[]  $handlers
     */
    public function setHandlers(array $handlers): void
    {
        $this->handlers = $handlers;
    }
    
}
