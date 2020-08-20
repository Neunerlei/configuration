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
 * Last modified: 2020.07.13 at 20:14
 */

declare(strict_types=1);


namespace Neunerlei\Configuration\Util;


use MJS\TopSort\Implementations\FixedArraySort;

/**
 * Class IntuitiveTopSorter
 *
 * A topological sorter, build on top of the ArraySort implementation.
 * However it will try to avoid moving the $pivotNode to the $target node and therefore keep the original item order if
 * possible. This means in a list like: ['a', 'b', 'c'] that 'a' AFTER 'c' results in: ['b', 'c', 'a'] instead of
 * ['c', 'a', 'b'] which would be the default and really counter intuitive. It also means that 'c' BEFORE 'a' results
 * in ['c', 'a', 'b'] as well, instead of the default: ['b', 'c', 'a']
 *
 * @package Neunerlei\Configuration\Util
 */
class IntuitiveTopSorter
{
    /**
     * The list to sort
     *
     * @var array
     */
    protected $list = [];
    
    /**
     * The list of order instructions to apply
     * It is a list of keys and the dependencies of the key
     *
     * @var array
     */
    protected $order = [];
    
    /**
     * A list of all items that should move
     *
     * @var array
     */
    protected $movingItems = [];
    
    /**
     * The list of items the $movingItems should be moved to
     *
     * @var array
     */
    protected $pivotItems = [];
    
    /**
     * IntuitiveTopSorter constructor.
     *
     * @param   array  $list  The list of items to sort
     */
    public function __construct(array $list)
    {
        $this->list = $list;
    }
    
    /**
     * Adds a new instruction to move the $item AFTER the $pivotItem without moving the $pivotItem itself.
     *
     * @param   string  $item       The item in the list to move
     * @param   string  $pivotItem  The item to move $item after
     *
     * @return $this
     */
    public function moveItemAfter(string $item, string $pivotItem): self
    {
        // Ignore invalid items or pivot items
        if (! in_array($item, $this->list, true) || ! in_array($pivotItem, $this->list, true)) {
            return $this;
        }
        
        // Store the instruction
        $this->pivotItems[]   = $pivotItem;
        $this->movingItems[]  = $item;
        $this->order[$item][] = $pivotItem;
        
        return $this;
    }
    
    /**
     * Adds a new instruction to move the $item BEFORE the $pivotItem without moving the $pivotItem itself.
     *
     * @param   string  $item       The item in the list to move
     * @param   string  $pivotItem  The item to move $item after
     *
     * @return $this
     */
    public function moveItemBefore(string $item, string $pivotItem): self
    {
        // Ignore invalid items or pivot items
        if (! in_array($item, $this->list, true) || ! in_array($pivotItem, $this->list, true)) {
            return $this;
        }
        
        // Store the instruction
        $this->pivotItems[]        = $pivotItem;
        $this->movingItems[]       = $item;
        $this->order[$pivotItem][] = $item;
        
        return $this;
    }
    
    /**
     * Applies the registered instructions to the list of items and returns the resulting list
     *
     * Note: Yes, it's a hell of a method. The code tries to infer a list of 'dependencies'
     * based on the given sort order, to avoid counter-intuitive sorting results.
     *
     * @return array
     */
    public function sort(): array
    {
        if (empty($this->order)) {
            return $this->list;
        }
        
        // Prepare the order list
        $order = array_fill_keys($this->list, []);
        $order = array_merge($order, $this->order);
        
        // Iterate all items in the order
        $lastItem = null;
        foreach ($order as $item => $deps) {
            // Ignore items that are actively moved
            if (in_array($item, $this->movingItems, true)) {
                // Except they are pivots as well -> dangling item
                if (in_array($item, $this->pivotItems, true)) {
                    // Iterate the registered order and merge in additional dependencies #10 + #11
                    foreach ($this->order as $_item => $_deps) {
                        if (! in_array($item, $_deps, true)) {
                            continue;
                        }
                        $order[$item] = array_unique(
                            array_merge(
                                $order[$item],
                                array_diff($_deps, [$item])
                            ));
                    }
                }
                continue;
            }
            
            // Inject the last item if we have it
            if ($lastItem !== null) {
                $order[$item][] = $lastItem;
            }
            
            // Store this item as last item
            $lastItem = $item;
        }
        
        /**
         * Helper to traverse the list of dependencies recursively.
         *
         * @param   array     $dependencies  Will contain the list of all dependencies we found
         * @param   array     $deps          A list of direct dependencies for a single item
         * @param   callable  $traverser     The self reference for the recursion
         */
        $traverser = static function (array &$dependencies, array $deps, callable $traverser) use ($order): void {
            foreach ($deps as $dep) {
                if (in_array($dep, $dependencies, true)) {
                    continue;
                }
                $dependencies[] = $dep;
                $traverser($dependencies, $order[$dep], $traverser);
            }
        };
        
        // Traverse the order and generate the recursive map
        $orderRecursive = array_fill_keys($this->list, []);
        foreach ($order as $item => $deps) {
            $traverser($orderRecursive[$item], $deps, $traverser);
        }
        $order = $orderRecursive;
        unset($orderRecursive);
        
        // Iterate all statics (not actively moved elements) and find a list of items which NOT
        // actively depend on them. Therefor we can implicitly use them as dependencies.
        $statics = array_diff($this->list, $this->movingItems);
        foreach ($statics as $staticItem) {
            // Find all items which DON't depend on the current $staticItem
            $nonDependentItems = [];
            foreach ($order as $item => $deps) {
                if ($item === $staticItem || in_array($staticItem, $deps, true)) {
                    continue;
                }
                if (array_search($item, $this->list, true) > array_search($staticItem, $this->list, true)) {
                    continue;
                }
                $nonDependentItems[] = $item;
            }
            
            // Skip if we only have items with dependencies
            if (empty($nonDependentItems)) {
                continue;
            }
            
            // Inject all non-dependent items into the order
            $order[$staticItem] = array_unique(array_merge($order[$staticItem], $nonDependentItems));
        }
        
        // Sort the list
        return (new FixedArraySort($order))->doSort()->toArray();
    }
}
