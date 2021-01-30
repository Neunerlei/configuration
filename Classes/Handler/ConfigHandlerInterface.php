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
 * Last modified: 2020.07.06 at 11:11
 */

declare(strict_types=1);

namespace Neunerlei\Configuration\Handler;

use Neunerlei\Configuration\Util\ConfigContextAwareInterface;

/**
 * Interface ConfigHandlerInterface
 *
 * @package Neunerlei\Configuration\Handler
 *
 * @todo    setDefinition() should become part of this contract in the next major release
 */
interface ConfigHandlerInterface extends ConfigContextAwareInterface
{
    /**
     * Allows your handler to tell the loader where it finds its contents,
     * on which interfaces it listens or the order in which it should be executed.
     *
     * Use the given $configurator to provide the configuration
     *
     * @param   \Neunerlei\Configuration\Handler\HandlerConfigurator  $configurator
     */
    public function configure(HandlerConfigurator $configurator): void;

    /**
     * Executed ONCE, before the ConfigDefinition is processed.
     * Allows you to initialize a configurator object or to prepare global data.
     */
    public function prepare(): void;

    /**
     * Executed once, for each registered configuration class we found for this handler.
     * Your handler has to do something with the class.
     *
     * @param   string  $class  The current configuration class to process
     *
     * @todo a second parameter $isOverride could be implemented in the next major release
     *       to tell the handler if the given class is an override or not. This would resolve the
     *       need to access the configDefinition?
     */
    public function handle(string $class): void;

    /**
     * Executed ONCE, after handle() was executed for each configuration class.
     * Allows you to persist the collected data in the config state or to pull down
     * global data if required.
     */
    public function finish(): void;
}
