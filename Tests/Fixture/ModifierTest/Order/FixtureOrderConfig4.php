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
 * Last modified: 2020.07.14 at 12:39
 */

declare(strict_types=1);


namespace Neunerlei\ConfigTests\Fixture\ModifierTest\Order;


use Neunerlei\ConfigTests\Fixture\FixtureTestConfigInterface;
use Neunerlei\ConfigTests\Fixture\ModifierTest\Order\Override\FixtureOrderConfig5;
use Neunerlei\Configuration\Modifier\Builtin\Order\ModifyConfigOrderInterface;

class FixtureOrderConfig4 implements FixtureTestConfigInterface, ModifyConfigOrderInterface
{
    /**
     * @inheritDoc
     */
    public static function setConfigOrder(array &$executeMeBefore, array &$executeMeAfter): void
    {
        $executeMeAfter[] = FixtureOrderConfig5::class;
    }
}
