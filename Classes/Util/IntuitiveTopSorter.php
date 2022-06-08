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
     * @deprecated will be removed without replacement in the next major release
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
     *
     * @note This implementation is heavily inspired by Oleksiy Gapotchenko's work
     * on stable topological sort algorithms.
     * @see  https://blog.gapotchenko.com/stable-topological-sort
     */
    public function sort(): array
    {
        if (empty($this->order)) {
            return $this->list;
        }

        // Prepare the order list
        $order = array_fill_keys($this->list, []);
        $order = array_merge($order, $this->order);

        $graph = new DependencyGraph($order);

        $sorted = $this->list;
        $length = count($this->list);

        for ($h = 0; $h < $length; $h++) {
            for ($i = 0; $i < $length; $i++) {
                for ($j = 0; $j < $i; $j++) {
                    if (! $graph->doesAHaveDirectDependencyOnB($sorted[$j], $sorted[$i])) {
                        continue;
                    }

                    $jOnI = $graph->doesAHaveTransitiveDependencyOnB($sorted[$j], $sorted[$i]);
                    $iOnJ = $graph->doesAHaveTransitiveDependencyOnB($sorted[$i], $sorted[$j]);

                    // If both are true, we have a circular dependency
                    if ($jOnI && $iOnJ) {
                        continue;
                    }

                    if (in_array($sorted[$j], $this->pivotItems, true)) {
                        $_j = $j;
                        $j  = $i;
                        $i  = $_j;
                        unset($_j);
                    }

                    $move = $sorted[$j];
                    unset($sorted[$j]);

                    array_splice($sorted, $i, 0, $move);
                    continue 3;
                }
            }

            break;
        }

        return $sorted;
    }
}
