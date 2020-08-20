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
 * Last modified: 2020.07.07 at 18:18
 */

declare(strict_types=1);


namespace Neunerlei\Configuration\Modifier\Builtin\Replace;


use Neunerlei\Configuration\Modifier\AbstractConfigModifier;
use Neunerlei\Configuration\Modifier\ModifierContext;

class ConfigReplaceModifier extends AbstractConfigModifier
{

    /**
     * @inheritDoc
     */
    public function apply(ModifierContext $context): void
    {
        // Find the replacing classes
        $replacingClasses = $this->findClassesWithInterface($context->getConfigClasses(),
            ModifyConfigByReplacingInterface::class);

        // Ignore if there is nothing to sort
        if (empty($replacingClasses)) {
            return;
        }

        // Replace the target classes with their override counterpart
        $classes = $context->getConfigClasses();
        foreach ($replacingClasses as $newClass) {
            // Remove the overriding class
            // Replace means replace -> if we don't find the target, well we have nothing to replace
            // so we remove the class completely
            $newClassIndex = array_search($newClass, $classes, true);
            unset($classes[$newClassIndex]);

            // Try to find the target class
            $targetClass = $newClass::setConfigClassToReplace();
            if (! in_array($targetClass, $classes, true)) {
                continue;
            }

            // Insert the overriding class at the position of the target class
            $targetClassIndex           = array_search($targetClass, $classes, true);
            $classes[$targetClassIndex] = $newClass;
        }

        // Update the context
        $context->setConfigClasses(array_values($classes));
    }

}
