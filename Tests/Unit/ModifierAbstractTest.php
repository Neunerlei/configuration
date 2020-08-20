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
 * Last modified: 2020.07.13 at 19:48
 */

declare(strict_types=1);


namespace Neunerlei\ConfigTests\Unit;


use InvalidArgumentException;
use Neunerlei\ConfigTests\Fixture\ModifierTest\General\Config\FixtureTestModifierConfig1;
use Neunerlei\ConfigTests\Fixture\ModifierTest\General\Config\FixtureTestModifierConfig2;
use Neunerlei\ConfigTests\Fixture\ModifierTest\General\Config\FixtureTestModifierConfig3;
use Neunerlei\ConfigTests\Fixture\ModifierTest\General\Config\FixtureTestModifierConfig4;
use Neunerlei\ConfigTests\Fixture\ModifierTest\General\FixtureTestModifierInterface;
use Neunerlei\ConfigTests\TestHelperTrait;
use Neunerlei\Configuration\Modifier\AbstractConfigModifier;
use PHPUnit\Framework\TestCase;

class ModifierAbstractTest extends TestCase
{
    use TestHelperTrait;
    
    public function testGetKey(): void
    {
        $mock = $this->makeInstance();
        $this->assertEquals(get_class($mock), $mock->getKey());
    }
    
    public function testFindClassesWithInterface(): void
    {
        $mock    = $this->makeInstance();
        $caller  = $this->makeCaller($mock, 'findClassesWithInterface');
        $classes = [
            FixtureTestModifierConfig1::class,
            FixtureTestModifierConfig2::class,
            FixtureTestModifierConfig3::class,
            FixtureTestModifierConfig4::class,
        ];
        $this->assertEquals([
            FixtureTestModifierConfig1::class,
            FixtureTestModifierConfig2::class,
        ], $caller($classes, [FixtureTestModifierInterface::class]));
        $this->assertEquals([
            FixtureTestModifierConfig1::class,
            FixtureTestModifierConfig2::class,
        ], $caller($classes, FixtureTestModifierInterface::class));
        $this->assertEquals([], $caller($classes, 'asdf'));
    }
    
    public function testFindClassesWithInterfaceFail(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $mock   = $this->makeInstance();
        $caller = $this->makeCaller($mock, 'findClassesWithInterface');
        $caller([], 123);
    }
    
    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|AbstractConfigModifier
     */
    protected function makeInstance()
    {
        return $this->getMockBuilder(AbstractConfigModifier::class)->getMockForAbstractClass();
    }
}
