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
 * Last modified: 2020.07.08 at 12:22
 */

declare(strict_types=1);


namespace Neunerlei\ConfigExample\Plugins\plugin2\Config;


use Neunerlei\ConfigExample\Plugins\plugin1\Handler\PluginHandler\ExampleConfigurePluginInterface;
use Neunerlei\ConfigExample\Plugins\plugin1\Handler\PluginHandler\ExamplePluginConfigurator;
use Neunerlei\ConfigExample\Project\Config\RuntimeConfig;
use Neunerlei\ConfigExample\Project\Handler\RuntimeHandler\ExampleConfigureRuntimeInterface;
use Neunerlei\ConfigExample\Project\Handler\RuntimeHandler\ExampleRuntimeConfigurator;
use Neunerlei\Configuration\Modifier\Builtin\Order\ModifyConfigOrderInterface;

/**
 * Class PluginRuntimeConfig
 *
 * You can configure multiple handlers with the same classes
 *
 * @package Neunerlei\ConfigExample\Plugins\plugin2\Config
 */
class PluginRuntimeConfig implements
    ExampleConfigureRuntimeInterface,
    ExampleConfigurePluginInterface,
    ModifyConfigOrderInterface
{
    /**
     * @inheritDoc
     */
    public static function setConfigOrder(array &$executeMeBefore, array &$executeMeAfter): void
    {
        $executeMeAfter[] = RuntimeConfig::class;
    }

    /**
     * @inheritDoc
     */
    public static function configureRuntime(ExampleRuntimeConfigurator $configurator): void
    {
        $configurator->addInstance(new class {
            public $me = 'plugin2';
        });
    }

    /**
     * @inheritDoc
     */
    public static function configurePlugin(ExamplePluginConfigurator $configurator): void
    {
        $configurator->addToList('plugin 2 value: Yes, you can configure plugins from other plugins');
    }


}
