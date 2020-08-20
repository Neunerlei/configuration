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
 * Last modified: 2020.07.08 at 11:47
 */

declare(strict_types=1);


namespace Neunerlei\ConfigExample\Project\Handler\RuntimeHandler;


use Neunerlei\Configuration\State\ConfigState;

class ExampleRuntimeConfigurator
{
    /**
     * A list of instances, that can not be serialized into a JSON
     *
     * @var array
     */
    protected $instances = [];
    
    /**
     * Adds a instance of something, that can not be serialized into json
     *
     * @param   object  $instance
     *
     * @return $this
     */
    public function addInstance(object $instance): self
    {
        $this->instances[] = $instance;
        
        return $this;
    }
    
    /**
     * Finish the state
     *
     * @param   \Neunerlei\Configuration\State\ConfigState  $state
     *
     * @internal
     */
    public function finish(ConfigState $state): void
    {
        $state->set('runtimeInstances', $this->instances);
    }
}
