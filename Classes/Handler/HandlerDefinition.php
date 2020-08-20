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
 * Last modified: 2020.07.06 at 12:50
 */

declare(strict_types=1);


namespace Neunerlei\Configuration\Handler;


use Neunerlei\Configuration\Loader\LoaderContext;

class HandlerDefinition
{
    /**
     * The name of the class represented by this definition
     *
     * @var string
     */
    public $className;

    /**
     * The instance this configuration applies to
     *
     * @var \Neunerlei\Configuration\Handler\ConfigHandlerInterface|null
     */
    public $handler;

    /**
     * True as long as override configurations are allowed
     *
     * @var bool
     */
    public $allowOverride = true;

    /**
     * Additional locations inside the $locations list where
     * we should look for overrides.
     * By default we will look for an "Override" directory
     *
     * @var string[]
     */
    public $overrideLocations = [];

    /**
     * A list of locations, relative to the registered root directories
     * where to look for configuration classes
     *
     * @var string[]
     */
    public $locations = [];

    /**
     * The list of interfaces we should listen to
     *
     * @var array
     */
    public $interfaces = [];

    /**
     * The list of other handler classes that get overwritten by this one
     *
     * @var array
     */
    public $overrides = [];

    /**
     * A list of other handler classes that should be executed BEFORE this handler
     *
     * @var array
     */
    public $after = [];

    /**
     * A list of other handler classes that should be executed AFTER this handler
     *
     * @var array
     */
    public $before = [];

    /**
     * Contains raw data that should be added to the config state object
     * before the handler gets executed.
     * NOTE: The data has to be JSON serializable!
     *
     * @var array
     */
    public $defaultState = [];

    /**
     * A list of default configuration classes which will be executed in front of all other classes
     *
     * @var array
     */
    public $defaultConfigClasses = [];

    /**
     * Dehydrates this object into a plain array that can be JSON encoded into the cache
     *
     * @return array
     */
    public function dehydrate(): array
    {
        $definition = get_object_vars($this);
        if ($this->handler !== null) {
            $definition['handler'] = get_class($this->handler);
        }

        return $definition;
    }

    /**
     * Rehydrates this object from a plain array, which was stored in a JSON cache
     *
     * @param   \Neunerlei\Configuration\Loader\LoaderContext  $loaderContext
     * @param   array                                          $definition
     *
     * @return \Neunerlei\Configuration\Handler\HandlerDefinition
     */
    public static function hydrate(LoaderContext $loaderContext, array $definition): HandlerDefinition
    {
        // Try to find the handler in the global handler list
        foreach ($loaderContext->handlers as $possibleHandler) {
            if ($possibleHandler instanceof $definition['handler']) {
                $definition['handler'] = $possibleHandler;
                break;
            }
        }
        // Reinstantiate the handler
        if (is_string($definition['handler'])) {
            $definition['handler'] = $loaderContext->getInstance($definition['handler']);
        }

        // Rehydrate myself
        $self = new static();
        foreach ($definition as $k => $v) {
            $self->$k = $v;
        }

        return $self;
    }
}
