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
 * Last modified: 2020.07.06 at 10:58
 */

declare(strict_types=1);


namespace Neunerlei\Configuration\Handler;


use Neunerlei\Arrays\Arrays;

class HandlerConfigurator
{
    /**
     * The definition, configured by this class
     *
     * @var \Neunerlei\Configuration\Handler\HandlerDefinition
     */
    protected $definition;

    /**
     * HandlerConfigurator constructor.
     *
     * @param   \Neunerlei\Configuration\Handler\HandlerDefinition  $definition
     */
    public function __construct(HandlerDefinition $definition)
    {
        $this->definition = $definition;
    }

    /**
     * Toggles the handling of override classes (True by default)
     *
     * @param   bool  $state
     *
     * @return $this
     */
    public function setAllowOverride(bool $state): self
    {
        $this->definition->allowOverride = $state;

        return $this;
    }

    /**
     * Registers a new override location. The given value can be either a path,
     * which will be searched relative to a handlers search location, or a glob path,
     * which is handled relative to a handlers search location.
     *
     * If a given handler class not exists, it will be ignored silently.
     *
     * @param   string  $globPattern
     *
     * @return $this
     */
    public function registerOverrideLocation(string $globPattern): self
    {
        $this->definition->overrideLocations[] = $globPattern;

        return $this;
    }

    /**
     * Registers a new location. The given path will be resolved relative
     * to all registered "rootLocations". The given value can be either a path,
     * which will be searched relative to all registered root locations, or a glob path,
     * which is handled relative to the root locations.
     *
     * @param   string  $globPattern
     *
     * @return $this
     */
    public function registerLocation(string $globPattern): self
    {
        $this->definition->locations[] = $globPattern;

        return $this;
    }

    /**
     * Registers an interface the config classes, that should be collected for this
     * handler must implement in order to be a valid target.
     *
     * @param   string  $interfaceName  The name of an interface
     *
     * @return $this
     */
    public function registerInterface(string $interfaceName): self
    {
        $this->definition->interfaces[] = $interfaceName;

        return $this;
    }

    /**
     * Allows you to override another handler class with this one. Can be called multiple times
     * in order to replace multiple handlers with this one.
     *
     * @param   string  $otherHandlerClass  The name of the handler class to override with this one
     *
     * @return $this
     */
    public function registerAsOverrideFor(string $otherHandlerClass): self
    {
        $this->definition->overrides[] = $otherHandlerClass;

        return $this;
    }

    /**
     * Allows you to set the priority of the handler in relation to other handlers.
     * Every handler you specify with this method will be executed AFTER this handler.
     * If a given handler class not exists, it will be ignored silently.
     *
     * @param   string  $otherHandlerClass
     *
     * @return $this
     */
    public function executeThisHandlerBefore(string $otherHandlerClass): self
    {
        $this->definition->before[] = $otherHandlerClass;

        return $this;
    }

    /**
     * Allows you to set the priority of the handler in relation to other handlers.
     * Every handler you specify with this method will be executed BEFORE this handler.
     * If a given handler class not exists, it will be ignored silently.
     *
     * @param   string  $otherHandlerClass
     *
     * @return $this
     */
    public function executeThisHandlerAfter(string $otherHandlerClass): self
    {
        $this->definition->after[] = $otherHandlerClass;

        return $this;
    }

    /**
     * Registers a default configuration class which will be executed in front of all other classes,
     * that were found in the registered locations. With this you can provide a base configuration
     * for this handler if you want.
     *
     * @param   string       $class      Name of a class that implements the correct configuration interface
     * @param   string|null  $namespace  Optional namespace for this class, to simulate the normal configuration lookup.
     *                                   If empty the name of the handler class is used
     *
     * @return $this
     */
    public function registerDefaultConfigClass(string $class, ?string $namespace = null): self
    {
        $this->definition->defaultConfigClasses[$class] = $namespace ??
                                                          $this->definition->handler === null ?
                                                              'HANDLER' : get_class($this->definition->handler);

        return $this;
    }

    /**
     * Allows you to set raw, default configuration data which will be injected into the
     * state object before the handler executes the configuration classes.
     *
     * NOTE: The data has to be JSON serializable!
     * NOTE 2: If this method is executed multiple times the data gets merged into each other.
     *
     * @param   array  $state
     *
     * @return $this
     */
    public function registerDefaultState(array $state): self
    {
        if (empty($this->definition->defaultState)) {
            $this->definition->defaultState = $state;
        } else {
            $this->definition->defaultState = Arrays::merge($this->definition->defaultState, $state);
        }

        return $this;
    }

    /**
     * Returns the low level handler definition, to modify the raw instance for some edge cases.
     *
     * @return \Neunerlei\Configuration\Handler\HandlerDefinition
     */
    public function getDefinition(): HandlerDefinition
    {
        return $this->definition;
    }
}
