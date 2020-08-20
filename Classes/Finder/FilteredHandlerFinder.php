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
 * Last modified: 2020.07.08 at 14:32
 */

declare(strict_types=1);


namespace Neunerlei\Configuration\Finder;


use Neunerlei\Configuration\Handler\HandlerDefinition;
use Neunerlei\Configuration\Loader\LoaderContext;

class FilteredHandlerFinder extends HandlerFinder
{
    /**
     * The list of interfaces that are NOT allowed to be implemented by the found handlers
     *
     * @var array
     */
    protected $ignoreWithInterface;
    
    /**
     * The list of interfaces that is REQUIRED to be implemented by the found handlers
     *
     * @var array
     */
    protected $allowWithInterface;
    
    /**
     * FilteredHandlerFinder constructor.
     *
     * @param   array  $ignoreWithInterface  A list of interfaces that are NOT allowed to be implemented by the handlers
     * @param   array  $allowWithInterface   A list of interfaces that is REQUIRED to be implemented by the handlers
     */
    public function __construct(array $ignoreWithInterface, array $allowWithInterface)
    {
        $this->ignoreWithInterface = $ignoreWithInterface;
        $this->allowWithInterface  = $allowWithInterface;
    }
    
    /**
     * @inheritDoc
     */
    protected function findDefinitions(LoaderContext $loaderContext): array
    {
        $definitions = parent::findDefinitions($loaderContext);
        
        return array_filter($definitions, function (HandlerDefinition $definition): bool {
            $interfaces = class_implements($definition->className);
            
            // Check if one of the interfaces is ignored
            if (! empty($this->ignoreWithInterface)
                && count(array_intersect($this->ignoreWithInterface, $interfaces)) !== 0) {
                return false;
            }
            
            // Check if the class implements at least one of the required interfaces
            if (! empty($this->allowWithInterface)
                && count(array_intersect($this->allowWithInterface, $interfaces)) === 0) {
                return false;
            }
            
            // This is allowed
            return true;
        });
    }
}
