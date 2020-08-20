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
 * Last modified: 2020.07.07 at 19:10
 */

declare(strict_types=1);


namespace Neunerlei\Configuration\Util;


use Neunerlei\Configuration\Handler\HandlerDefinition;
use Neunerlei\Configuration\Loader\ConfigContext;

trait ConfigDefinitionGetterSetterTrait
{
    /**
     * Returns the handler definition this definition does apply to
     *
     * @return \Neunerlei\Configuration\Handler\HandlerDefinition
     */
    public function getHandlerDefinition(): HandlerDefinition
    {
        return $this->handlerDefinition;
    }
    
    /**
     * Returns the context object the config is generated for
     *
     * @return \Neunerlei\Configuration\Loader\ConfigContext
     */
    public function getConfigContext(): ConfigContext
    {
        return $this->configContext;
    }
    
    /**
     * The list of classes that have been registered as "override".
     * Those classes have already been merged into $configClasses!
     *
     * @return array
     */
    public function getOverrideConfigClasses(): array
    {
        return $this->overrideConfigClasses;
    }
    
    /**
     * Returns the list of config classes we should dispatch the handler for
     *
     * @return array
     */
    public function getConfigClasses(): array
    {
        return $this->configClasses;
    }
    
    /**
     * Updates the list of config classes we should dispatch the handler for
     *
     * @param   array  $configClasses
     */
    public function setConfigClasses(array $configClasses): void
    {
        $this->configClasses = $configClasses;
    }
    
    /**
     * Returns the list of config classes and their matching namespaces
     *
     * @return array
     */
    public function getClassNamespaceMap(): array
    {
        return $this->classNamespaceMap;
    }
    
    /**
     * Updates the list of config classes and their matching namespaces
     *
     * @param   array  $classNamespaceMap
     */
    public function setClassNamespaceMap(array $classNamespaceMap): void
    {
        $this->classNamespaceMap = $classNamespaceMap;
    }
}
