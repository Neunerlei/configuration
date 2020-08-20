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
 * Last modified: 2020.07.07 at 17:45
 */

declare(strict_types=1);


namespace Neunerlei\Configuration\Modifier\Builtin\Order;


use Neunerlei\Configuration\Modifier\AbstractConfigModifier;
use Neunerlei\Configuration\Modifier\ModifierContext;
use Neunerlei\Configuration\Util\IntuitiveTopSorter;

class ConfigOrderModifier extends AbstractConfigModifier
{
    
    /**
     * @inheritDoc
     */
    public function apply(ModifierContext $context): void
    {
        // Find the sortable classes
        $sortableClasses = $this->findClassesWithInterface($context->getConfigClasses(),
            ModifyConfigOrderInterface::class);
        
        // Ignore if there is nothing to sort
        if (empty($sortableClasses)) {
            return;
        }
        
        // Build a list of the desired order
        $sorter = new IntuitiveTopSorter($context->getConfigClasses());
        foreach ($sortableClasses as $sortableClass) {
            $before = [];
            $after  = [];
            call_user_func_array([$sortableClass, 'setConfigOrder'], [&$before, &$after]);
            foreach ($after as $otherClass) {
                $sorter->moveItemAfter($sortableClass, $otherClass);
            }
            foreach ($before as $otherClass) {
                $sorter->moveItemBefore($sortableClass, $otherClass);
            }
        }
        
        // Sort the list based on the dependencies
        $context->setConfigClasses($sorter->sort());
    }
    
}
