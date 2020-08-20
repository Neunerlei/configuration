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
 * Last modified: 2020.07.07 at 18:51
 */

declare(strict_types=1);


namespace Neunerlei\Configuration\Event;


use Neunerlei\Configuration\Handler\HandlerDefinition;
use Neunerlei\Configuration\Loader\AbstractConfigDefinition;
use Neunerlei\Configuration\Util\ConfigDefinitionGetterSetterTrait;

class ConfigDefinitionFilterEvent extends AbstractConfigDefinition
{
    use ConfigDefinitionGetterSetterTrait;
    
    /**
     * Updates the handler definition this definition does apply to
     *
     * @param   \Neunerlei\Configuration\Handler\ConfigHandlerInterface  $definition
     */
    public function setHandlerDefinition(HandlerDefinition $definition): void
    {
        $this->handlerDefinition = $definition;
    }
    
    /**
     * Updates the list of classes that have been registered as "override".
     * Those classes have already been merged into $configClasses!
     *
     * @param   array  $overrideConfigClasses
     */
    public function setOverrideConfigClasses(array $overrideConfigClasses): void
    {
        $this->overrideConfigClasses = $overrideConfigClasses;
    }
    
    
}
