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
 * Last modified: 2020.07.06 at 10:40
 */

declare(strict_types=1);


namespace Neunerlei\Configuration\Loader;


use Neunerlei\Configuration\State\ConfigState;

class ConfigContext
{
    /**
     * The current configuration namespace
     *
     * @var string
     */
    protected $namespace = "LIMBO";
    
    /**
     * The loader configuration object
     *
     * @var \Neunerlei\Configuration\Loader\LoaderContext
     */
    protected $loaderContext;
    
    /**
     * The configuration state we are currently gathering
     *
     * @var \Neunerlei\Configuration\State\ConfigState
     */
    protected $state;
    
    /**
     * Initializes the instance by injecting the runtime variables
     *
     * @param   \Neunerlei\Configuration\Loader\LoaderContext  $loaderContext
     * @param   ConfigState                                    $state
     */
    public function initialize(LoaderContext $loaderContext, ConfigState $state): void
    {
        $this->loaderContext = $loaderContext;
        $this->state         = $state;
    }
    
    /**
     * Returns the namespace we are currently running the configuration for
     * The namespace is a generic way of applying context aware configuration.
     * Based on your setup/framework this might be the name of your plugin or your extension.
     *
     * @return string
     */
    public function getNamespace(): string
    {
        return $this->namespace;
    }
    
    /**
     * Allows you to temporarily override the namespace to something else.
     * This allows you to simulate the configuration being done from another namespace.
     * The original namespace is automatically restored after the $callback was executed.
     * Nesting this method inside each other is not recommended, but possible
     *
     * @param   string    $namespace  The namespace you want to use for your actions
     * @param   callable  $callback   Everything executed in this callback will be processed
     *                                as executed in the given $namespace.
     *
     * @return $this
     */
    public function runWithNamespace(string $namespace, callable $callback): self
    {
        $namespaceBackup = $this->namespace;
        $this->namespace = $namespace;
        try {
            $callback($this);
        } finally {
            $this->namespace = $namespaceBackup;
        }
        
        return $this;
    }
    
    /**
     * Returns the unique type of configuration we are currently gathering.
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->loaderContext->type;
    }
    
    /**
     * Returns something like "dev"/"prod"/"stage" or similar to
     * describe your current environment
     *
     * @return string
     */
    public function getEnvironment(): string
    {
        return $this->loaderContext->environment;
    }
    
    /**
     * The state is the low-level registry you are filling up
     * with data while the configuration is passed through the handlers.
     * Each handler should write it's information in here.
     *
     * @return \Neunerlei\Configuration\State\ConfigState
     */
    public function getState(): ConfigState
    {
        return $this->state;
    }
    
    /**
     * Returns the root-level context object the config loader framework.
     * NOTE: Be careful with the stuff in this object, changes may break your setup.
     *
     * @return \Neunerlei\Configuration\Loader\LoaderContext
     */
    public function getLoaderContext(): LoaderContext
    {
        return $this->loaderContext;
    }
}
