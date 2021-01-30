<?php
/*
 * Copyright 2021 LABOR.digital
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
 * Last modified: 2021.01.30 at 19:49
 */

declare(strict_types=1);


namespace Neunerlei\ConfigTests\Fixture\ConfigFinderTest\Items\Item3\Config;


use Neunerlei\ConfigTests\Fixture\FixtureTestConfigInterface;

/**
 * Interface FixtureExtendingInterface
 *
 * Extending interfaces MUST not be found by the config finder
 *
 * @package Neunerlei\ConfigTests\Fixture\ConfigFinderTest\Items\Item3\Config
 */
interface FixtureExtendingInterface extends FixtureTestConfigInterface
{

}
