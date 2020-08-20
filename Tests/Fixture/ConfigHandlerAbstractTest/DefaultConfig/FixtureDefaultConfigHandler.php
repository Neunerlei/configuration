<?php
/*
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
 * Last modified: 2020.08.09 at 15:32
 */

declare(strict_types=1);


namespace Neunerlei\ConfigTests\Fixture\ConfigHandlerAbstractTest\DefaultConfig;


use Neunerlei\ConfigTests\Fixture\FixtureTestHandler;
use Neunerlei\Configuration\Handler\HandlerConfigurator;

class FixtureDefaultConfigHandler extends FixtureTestHandler
{
    /**
     * True if we should register a config class
     * False if we should register a raw state array
     *
     * @var bool
     */
    protected $useConfigClass = false;

    public function useConfigClass(): self
    {
        $this->useConfigClass = true;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function configure(HandlerConfigurator $configurator): void
    {
        $this->interfaceName = FixtureDefaultConfigInterface::class;
        parent::configure($configurator);

        if ($this->useConfigClass) {
            $configurator->registerDefaultConfigClass(FixtureDefaultConfigClass::class);
        } else {
            $configurator->registerDefaultState(['my' => 'key']);
            $configurator->registerDefaultState(['your' => 'key']);
            $configurator->registerDefaultState(['our' => ['key' => ['is' => 'key']]]);
            $configurator->registerDefaultState(['your' => 'yourKey']);
        }
    }

    /**
     * @inheritDoc
     */
    public function handle(string $class): void
    {
        /** @var FixtureDefaultConfigInterface $class */
        $class::configure($this->context->getState());
    }


}
