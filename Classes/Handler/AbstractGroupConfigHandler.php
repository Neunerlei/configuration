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
 * Last modified: 2020.07.08 at 12:52
 */

declare(strict_types=1);


namespace Neunerlei\Configuration\Handler;

abstract class AbstractGroupConfigHandler extends AbstractConfigHandler
{
    /**
     * The list of groups that should be handled
     *
     * @var array
     */
    protected $groups = [];
    
    /**
     * Must return the group key of the given class
     *
     * @param   string  $class  The class to get the group key for
     *
     * @return string
     */
    abstract protected function getGroupKeyOfClass(string $class): string;
    
    /**
     * Use this as equivalent to prepare() which is occupied by this abstract
     */
    abstract public function prepareHandler(): void;
    
    /**
     * Use this as equivalent to finish() which is occupied by this abstract
     */
    abstract public function finishHandler(): void;
    
    /**
     * Executed once before the handleGroupItem(); methods for the group will processed
     *
     * @param   string  $groupKey      The key of the group that gets processed now
     * @param   array   $groupClasses  The list of all classes in the group
     */
    abstract public function prepareGroup(string $groupKey, array $groupClasses): void;
    
    /**
     * Handled once for every item in a class group
     *
     * @param   string  $class
     */
    abstract public function handleGroupItem(string $class): void;
    
    /**
     * Executed once after all handleGroupItem(); methods for the group have been processed
     *
     * @param   string  $groupKey      The key of the group that was processed
     * @param   array   $groupClasses  The list of all classes in the group
     */
    abstract public function finishGroup(string $groupKey, array $groupClasses): void;
    
    /**
     * @inheritDoc
     */
    public function prepare(): void
    {
        $this->groups = [];
        $this->prepareHandler();
    }
    
    /**
     * @inheritDoc
     */
    public function handle(string $class): void
    {
        // Find the key of the group
        $groupKey = $this->getGroupKeyOfClass($class);
        
        // Store the class in the group
        $this->groups[$groupKey][] = [$this->context->getNamespace(), $class];
    }
    
    /**
     * @inheritDoc
     */
    public function finish(): void
    {
        foreach ($this->groups as $groupKey => $items) {
            // Make sure even numeric keys are handled as strings
            $groupKey = (string)$groupKey;
            
            // Use the first available namespace as a default
            $namespace = reset($items)[0];
            $this->context->runWithNamespace($namespace, function () use ($groupKey, $items) {
                $groupClasses = array_map(function (array $item) { return $item[1]; }, $items);
                
                // Prepare the group
                $this->prepareGroup($groupKey, $groupClasses);
                
                // Iterate the items in the group
                foreach ($items as $item) {
                    $this->context->runWithNamespace($item[0], function () use ($item) {
                        $this->handleGroupItem($item[1]);
                    });
                }
                
                // Finish up the group
                $this->finishGroup($groupKey, $groupClasses);
                
            });
        }
        $this->finishHandler();
    }
    
}
