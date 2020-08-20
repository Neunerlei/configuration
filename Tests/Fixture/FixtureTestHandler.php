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
 * Last modified: 2020.07.14 at 13:11
 */

declare(strict_types=1);


namespace Neunerlei\ConfigTests\Fixture;


use Neunerlei\Configuration\Handler\AbstractConfigHandler;
use Neunerlei\Configuration\Handler\HandlerConfigurator;
use Neunerlei\Configuration\Loader\ConfigContext;

/**
 * Class FixtureTestHandler
 *
 * Generic null handler implementation for the test cases
 *
 * @package Neunerlei\ConfigTests\Fixture
 */
class FixtureTestHandler extends AbstractConfigHandler
{
    /**
     * The location to register
     *
     * @var string
     */
    protected $location;

    /**
     * The interface to register
     *
     * @var string
     */
    protected $interfaceName;

    /**
     * Optional additional configuration as callable.
     * The callable receives the configurator as argument
     *
     * @var callable|null
     */
    protected $additionalConfig;

    /**
     * The list of iterated classes
     *
     * @var array
     */
    public $classes = [];

    /**
     * The handler configurator instance which was passed to this object
     *
     * @var HandlerConfigurator|null
     */
    public $configurator;

    /**
     * True if the configure method was called
     *
     * @var bool
     */
    public $configureCalled = false;

    /**
     * True if the prepare method was called
     *
     * @var bool
     */
    public $prepareCalled = false;

    /**
     * True if the finish method was called
     *
     * @var bool
     */
    public $finishCalled = false;

    /**
     * FixtureTestHandler constructor.
     *
     * @param   string         $location
     * @param   string|null    $interfaceName
     * @param   callable|null  $additionalConfig
     */
    public function __construct(
        string $location = 'Config',
        ?string $interfaceName = null,
        ?callable $additionalConfig = null
    ) {
        $this->location         = $location;
        $this->interfaceName    = $interfaceName ?? FixtureTestConfigInterface::class;
        $this->additionalConfig = $additionalConfig;
    }

    /**
     * @inheritDoc
     */
    public function configure(HandlerConfigurator $configurator): void
    {
        $this->configureCalled = true;
        $this->configurator    = $configurator;
        $configurator->registerInterface($this->interfaceName);
        $configurator->registerLocation($this->location);
        if ($this->additionalConfig !== null) {
            ($this->additionalConfig)($configurator);
        }
    }

    /**
     * @inheritDoc
     */
    public function prepare(): void
    {
        $this->prepareCalled = true;
    }

    /**
     * @inheritDoc
     */
    public function handle(string $class): void
    {
        $this->classes[] = $class;
    }

    /**
     * @inheritDoc
     */
    public function finish(): void
    {
        $this->finishCalled = true;
    }

    /**
     * Helper to retrieve the current context object
     *
     * @return \Neunerlei\Configuration\Loader\ConfigContext|null
     */
    public function getContext(): ?ConfigContext
    {
        return $this->context;
    }
}
