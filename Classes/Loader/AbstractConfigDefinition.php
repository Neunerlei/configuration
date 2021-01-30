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
 * Last modified: 2020.07.07 at 17:28
 */

declare(strict_types=1);


namespace Neunerlei\Configuration\Loader;


use Neunerlei\Configuration\Handler\HandlerDefinition;

abstract class AbstractConfigDefinition
{
    /**
     * The handler definition this config definition applies to
     *
     * @var HandlerDefinition
     */
    protected $handlerDefinition;

    /**
     * The context object the config is generated for
     *
     * @var \Neunerlei\Configuration\Loader\ConfigContext
     */
    protected $configContext;

    /**
     * The list of config classes we should dispatch the handler for
     *
     * @var array
     */
    protected $configClasses;

    /**
     * The list of classes that have been registered as "override".
     * Those classes have already been merged into $configClasses!
     * This property is designed for modifiers
     *
     * @var array
     */
    protected $overrideConfigClasses;

    /**
     * The list of config classes and their matching namespaces
     *
     * @var array
     */
    protected $classNamespaceMap;

    /**
     * AbstractConfigStack constructor.
     *
     * @param   \Neunerlei\Configuration\Handler\HandlerDefinition  $handlerDefinition
     * @param   \Neunerlei\Configuration\Loader\ConfigContext       $context
     * @param   array                                               $configClasses
     * @param   array                                               $overrideConfigClasses
     * @param   array                                               $classNamespaceMap
     */
    public function __construct(
        HandlerDefinition $handlerDefinition,
        ConfigContext $context,
        array $configClasses,
        array $overrideConfigClasses,
        array $classNamespaceMap
    ) {
        $this->handlerDefinition     = $handlerDefinition;
        $this->configContext         = $context;
        $this->configClasses         = $configClasses;
        $this->classNamespaceMap     = $classNamespaceMap;
        $this->overrideConfigClasses = $overrideConfigClasses;
    }

    /**
     * Returns the handler definition this config definition applies to
     *
     * @return \Neunerlei\Configuration\Handler\HandlerDefinition
     */
    public function getHandlerDefinition(): HandlerDefinition
    {
        return $this->handlerDefinition;
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
     * Returns the list of classes that have been registered as "override".
     *
     * @return array
     */
    public function getOverrideConfigClasses(): array
    {
        return $this->overrideConfigClasses;
    }

    /**
     * Returns true if the given class was registered as "override", false if not.
     *
     * @param   string  $className  The name of the class to check for
     *
     * @return bool
     */
    public function isOverride(string $className): bool
    {
        return in_array($className, $this->overrideConfigClasses, true);
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
}
