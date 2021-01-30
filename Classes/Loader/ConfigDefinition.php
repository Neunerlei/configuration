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
 * Last modified: 2020.07.06 at 19:26
 */

declare(strict_types=1);


namespace Neunerlei\Configuration\Loader;


use Neunerlei\Configuration\Handler\HandlerDefinition;

class ConfigDefinition extends AbstractConfigDefinition
{
    /**
     * Processes this definition by iterating all classes with the handler instance
     */
    public function process(): void
    {
        // Initialize the handler
        $handler = $this->handlerDefinition->handler;
        $handler->setConfigContext($this->configContext);

        // todo this should become a default action in the next major release
        if (method_exists($handler, 'setDefinition')) {
            $handler->setDefinition($this);
        }

        if (! empty($this->handlerDefinition->defaultState)) {
            $this->configContext->getState()->setMultiple($this->handlerDefinition->defaultState);
        }

        // Ignore if there are no configurations
        if (empty($this->configClasses)) {
            return;
        }

        // Prepare the handler
        $handler->prepare();

        // Run the handler
        foreach ($this->configClasses as $class) {
            $this->configContext->runWithNamespace(
                $this->classNamespaceMap[$class],
                static function () use ($class, $handler) {
                    $handler->handle($class);
                }
            );
        }

        // Finish up
        $handler->finish();
    }

    /**
     * Dehydrates this object into a plain array that can be JSON encoded into the cache
     *
     * @return array
     */
    public function dehydrate(): array
    {
        $definition                      = get_object_vars($this);
        $definition['handlerDefinition'] = $this->handlerDefinition->dehydrate();
        $definition['configContext']     = null;

        return $definition;
    }

    /**
     * Rehydrates this object from a plain array, which was stored in a JSON cache
     *
     * @param   \Neunerlei\Configuration\Loader\LoaderContext  $loaderContext
     * @param   array                                          $definition
     *
     * @return \Neunerlei\Configuration\Loader\ConfigDefinition
     */
    public static function hydrate(LoaderContext $loaderContext, array $definition): ConfigDefinition
    {
        return new static(
            HandlerDefinition::hydrate($loaderContext, $definition['handlerDefinition']),
            $loaderContext->configContext,
            $definition['configClasses'],
            $definition['overrideConfigClasses'],
            $definition['classNamespaceMap']);
    }
}
