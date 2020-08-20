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
 * Last modified: 2020.07.08 at 13:13
 */

declare(strict_types=1);


namespace Neunerlei\ConfigExample\Project\ContentElement\Override;


use Neunerlei\ConfigExample\Project\Handler\ContentElement\AbstractContentElementConfiguration;
use Neunerlei\ConfigExample\Project\Handler\ContentElement\ExampleContentElementConfigurator;

/**
 * Class TextContentElementOverride
 *
 * Placing this class in an "Override" directory automatically makes sure that it will be
 * executed after the default TextContentElement config you will find in Plugins/plugin3/ContentElement.
 *
 * @package Neunerlei\ConfigExample\Project\ContentElement\Override
 */
class TextContentElement extends AbstractContentElementConfiguration
{
    /**
     * @inheritDoc
     */
    public static function configureElement(ExampleContentElementConfigurator $configurator): void
    {
        $configurator->setOption('foo', 'projectOverride');
    }
    
}
