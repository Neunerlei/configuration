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
 * Last modified: 2020.07.06 at 22:09
 */

declare(strict_types=1);


namespace Neunerlei\Configuration\Handler;


use Neunerlei\Configuration\Loader\ConfigDefinition;
use Neunerlei\Configuration\Util\ConfigContextAwareTrait;

abstract class AbstractConfigHandler implements ConfigHandlerInterface
{
    use ConfigContextAwareTrait;

    /**
     * Contains the configuration definition for this handler
     *
     * @var ConfigDefinition
     */
    protected $definition;

    /**
     * Allows the config definition to inject itself into the handler
     *
     * @param   \Neunerlei\Configuration\Loader\ConfigDefinition  $definition
     */
    public function setDefinition(ConfigDefinition $definition): void
    {
        $this->definition = $definition;
    }

    /**
     * Alias/Shortcut for $this->context->getLoaderContext()->getInstance($classname, $creationFallback)
     *
     * @param   string         $classname         The name of the class to instantiate
     * @param   callable|null  $creationFallback  Executed if no container is given, or the container
     *                                            does not know how to instantiate the class
     *
     * @return mixed
     * @see \Neunerlei\Configuration\Loader\LoaderContext::getInstance()
     */
    protected function getInstance(string $classname, ?callable $creationFallback = null)
    {
        return $this->context->getLoaderContext()->getInstance($classname, $creationFallback);
    }
}
