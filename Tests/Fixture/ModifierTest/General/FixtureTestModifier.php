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
 * Last modified: 2020.07.13 at 19:51
 */

declare(strict_types=1);


namespace Neunerlei\ConfigTests\Fixture\ModifierTest\General;


use Neunerlei\Configuration\Modifier\AbstractConfigModifier;
use Neunerlei\Configuration\Modifier\ModifierContext;

class FixtureTestModifier extends AbstractConfigModifier
{
    public $getKeyCalled = 0;
    public $applyCalled  = 0;
    public $modifierContext;
    
    /**
     * @inheritDoc
     */
    public function getKey(): string
    {
        $this->getKeyCalled++;
        
        return 'testModifier';
    }
    
    
    /**
     * @inheritDoc
     */
    public function apply(ModifierContext $context): void
    {
        $this->applyCalled++;
        $this->modifierContext = $context;
    }
    
}
