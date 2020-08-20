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
 * Last modified: 2020.07.13 at 11:42
 */

declare(strict_types=1);


namespace Neunerlei\Configuration\Event;


use Neunerlei\Configuration\Finder\ConfigFinderInterface;
use Neunerlei\Configuration\Loader\LoaderContext;

class ConfigFinderFilterEvent
{
    /**
     * The context for the loader instance itself
     *
     * @var \Neunerlei\Configuration\Loader\LoaderContext
     */
    protected $loaderContext;
    
    /**
     * The implementation that finds the config classes in the registered locations
     *
     * @var \Neunerlei\Configuration\Finder\ConfigFinderInterface
     */
    protected $configFinder;
    
    /**
     * ConfigFinderFilterEvent constructor.
     *
     * @param   \Neunerlei\Configuration\Finder\ConfigFinderInterface  $configFinder
     * @param   \Neunerlei\Configuration\Loader\LoaderContext          $loaderContext
     */
    public function __construct(ConfigFinderInterface $configFinder, LoaderContext $loaderContext)
    {
        $this->configFinder  = $configFinder;
        $this->loaderContext = $loaderContext;
    }
    
    /**
     * Returns the context for the loader instance
     *
     * @return \Neunerlei\Configuration\Loader\LoaderContext
     */
    public function getLoaderContext(): LoaderContext
    {
        return $this->loaderContext;
    }
    
    /**
     * Returns the implementation that finds the config classes in the registered locations
     *
     * @return \Neunerlei\Configuration\Finder\ConfigFinderInterface
     */
    public function getConfigFinder(): ConfigFinderInterface
    {
        return $this->configFinder;
    }
    
    /**
     * Updates the implementation that finds the config classes in the registered locations
     *
     * @param   \Neunerlei\Configuration\Finder\ConfigFinderInterface  $configFinder
     */
    public function setConfigFinder(ConfigFinderInterface $configFinder): void
    {
        $this->configFinder = $configFinder;
    }
}
