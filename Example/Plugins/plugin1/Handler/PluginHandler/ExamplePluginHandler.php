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
 * Last modified: 2020.07.08 at 11:19
 */

declare(strict_types=1);


namespace Neunerlei\ConfigExample\Plugins\plugin1\Handler\PluginHandler;


use Neunerlei\Configuration\Handler\AbstractConfigHandler;
use Neunerlei\Configuration\Handler\HandlerConfigurator;

class ExamplePluginHandler extends AbstractConfigHandler
{
    /**
     * @var \Neunerlei\ConfigExample\Plugins\plugin1\Handler\PluginHandler\ExamplePluginConfigurator
     */
    protected $configurator;
    
    /**
     * @inheritDoc
     */
    public function configure(HandlerConfigurator $configurator): void
    {
        $configurator->registerInterface(ExampleConfigurePluginInterface::class);
        $configurator->registerLocation('Config');
    }
    
    /**
     * @inheritDoc
     */
    public function prepare(): void
    {
        $this->configurator = $this->getInstance(ExamplePluginConfigurator::class);
    }
    
    /**
     * @inheritDoc
     */
    public function handle(string $class): void
    {
        call_user_func([$class, 'configurePlugin'], $this->configurator);
    }
    
    /**
     * @inheritDoc
     */
    public function finish(): void
    {
        $this->context->getState()->useNamespace('plugin1', [$this->configurator, 'finish']);
    }
    
}
