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
 * Last modified: 2020.07.15 at 23:04
 */

declare(strict_types=1);


namespace Neunerlei\ConfigExample\Project\Handler\NoopHandler;


use Neunerlei\Configuration\Handler\ConfigHandlerInterface;
use Neunerlei\Configuration\Handler\HandlerConfigurator;
use Neunerlei\Configuration\Loader\ConfigContext;

/**
 * Class ExampleNoopHandler
 *
 * Handlers that don't apply to any active configuration are simply ignored
 *
 * @package Neunerlei\ConfigExample\Project\Handler\NoopHandler
 */
class ExampleNoopHandler implements ConfigHandlerInterface
{
    /**
     * @inheritDoc
     */
    public function setConfigContext(ConfigContext $context): void
    {
    }
    
    /**
     * @inheritDoc
     */
    public function configure(HandlerConfigurator $configurator): void
    {
    }
    
    /**
     * @inheritDoc
     */
    public function prepare(): void
    {
    }
    
    /**
     * @inheritDoc
     */
    public function handle(string $class): void
    {
    }
    
    /**
     * @inheritDoc
     */
    public function finish(): void
    {
    }
    
}
