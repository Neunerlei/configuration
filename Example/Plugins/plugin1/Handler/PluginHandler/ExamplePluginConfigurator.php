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
 * Last modified: 2020.07.08 at 11:20
 */

declare(strict_types=1);


namespace Neunerlei\ConfigExample\Plugins\plugin1\Handler\PluginHandler;


use Neunerlei\Configuration\State\ConfigState;
use Neunerlei\Configuration\Util\ConfigContextAwareInterface;
use Neunerlei\Configuration\Util\ConfigContextAwareTrait;

class ExamplePluginConfigurator implements ConfigContextAwareInterface
{
    use ConfigContextAwareTrait;
    
    /**
     * A simple string option
     *
     * @var string|null
     */
    protected $option;
    
    /**
     * A list option
     *
     * @var array
     */
    protected $list = [];
    
    /**
     * Set a simple string as option
     *
     * @param   string  $value
     *
     * @return $this
     */
    public function setOption(string $value): self
    {
        $this->option = $value;
        
        return $this;
    }
    
    /**
     * Adds a namespace aware value to a list
     *
     * @param   string  $value
     *
     * @return $this
     */
    public function addToList(string $value): self
    {
        $this->list[$this->context->getNamespace()] = $value;
        
        return $this;
    }
    
    /**
     * Passes the configuration to the state
     *
     * @param   \Neunerlei\Configuration\State\ConfigState  $state
     *
     * @internal
     */
    public function finish(ConfigState $state): void
    {
        $state->set('option', $this->option);
        $state->set('list', $this->list);
    }
}
