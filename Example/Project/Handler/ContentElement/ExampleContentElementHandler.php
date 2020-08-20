<?php
declare(strict_types=1);
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
 * Last modified: 2020.07.08 at 12:27
 */

namespace Neunerlei\ConfigExample\Project\Handler\ContentElement;

use Neunerlei\Configuration\Handler\AbstractGroupConfigHandler;
use Neunerlei\Configuration\Handler\HandlerConfigurator;


class ExampleContentElementHandler extends AbstractGroupConfigHandler
{
    /**
     * @var \Neunerlei\ConfigExample\Project\Handler\ContentElement\ExampleContentElementConfigurator
     */
    protected $configurator;
    
    /**
     * The gathered list of content element information
     *
     * @var array
     */
    protected $contentElements = [];
    
    /**
     * @inheritDoc
     */
    public function configure(HandlerConfigurator $configurator): void
    {
        $configurator->registerLocation('ContentElement');
        $configurator->registerInterface(ExampleConfigureContentElementInterface::class);
        // Non existing interfaces will be ignored silently
        $configurator->registerInterface(NonExistingConfigureContentElementExampleInterface::class);
    }
    
    /**
     * @inheritDoc
     */
    protected function getGroupKeyOfClass(string $class): string
    {
        return call_user_func([$class, 'getContentElementKey']);
    }
    
    /**
     * @inheritDoc
     */
    public function prepareHandler(): void
    {
        // Nothing to do here
    }
    
    /**
     * @inheritDoc
     */
    public function finishHandler(): void
    {
        $this->context->getState()->set('contentElements', $this->contentElements);
    }
    
    /**
     * @inheritDoc
     */
    public function prepareGroup(string $groupKey, array $groupClasses): void
    {
        $this->configurator = new ExampleContentElementConfigurator($this->context->getNamespace(), $groupKey);
    }
    
    /**
     * @inheritDoc
     */
    public function handleGroupItem(string $class): void
    {
        call_user_func([$class, 'configureElement'], $this->configurator);
    }
    
    /**
     * @inheritDoc
     */
    public function finishGroup(string $groupKey, array $groupClasses): void
    {
        $this->contentElements[$groupKey] = $this->configurator->finish();
        $this->configurator               = null;
    }
    
}
