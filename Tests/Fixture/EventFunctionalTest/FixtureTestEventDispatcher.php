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
 * Last modified: 2020.07.16 at 12:17
 */

declare(strict_types=1);


namespace Neunerlei\ConfigTests\Fixture\EventFunctionalTest;


use Psr\EventDispatcher\EventDispatcherInterface;

class FixtureTestEventDispatcher implements EventDispatcherInterface
{
    
    /**
     * A list of event handlers
     *
     * @var callable[]
     */
    protected $handlers = [];
    
    /**
     * Executed when a event is dispatched we don't have a handler for
     *
     * @var callable
     */
    protected $unknownEventHandler;
    
    /**
     * A list of all handlers that have been triggered
     *
     * @var array
     */
    protected $triggeredHandlers = [];
    
    /**
     * @inheritDoc
     */
    public function dispatch(object $event)
    {
        $classname = get_class($event);
        if (isset($this->handlers[$classname])) {
            ($this->handlers[$classname])($event);
            $this->triggeredHandlers[] = $classname;
        } elseif (isset($this->unknownEventHandler)) {
            ($this->unknownEventHandler)($event);
        }
    }
    
    /**
     * Registers a new handler to test a single event instance
     *
     * @param   string    $classname
     * @param   callable  $handler
     */
    public function registerHandler(string $classname, callable $handler): void
    {
        $this->handlers[$classname] = $handler;
    }
    
    /**
     * Registers a callback which is executed when a event is dispatched we don't have a handler for
     *
     * @param   callable  $handler
     */
    public function registerOnUnknownEventHandler(callable $handler): void
    {
        $this->unknownEventHandler = $handler;
    }
    
    /**
     * True if all handlers have been triggered at least once
     *
     * @return bool
     */
    public function haveAllHandlersBeenTriggered(): bool
    {
        return empty(array_diff(array_unique($this->triggeredHandlers), array_keys($this->handlers)));
    }
}
