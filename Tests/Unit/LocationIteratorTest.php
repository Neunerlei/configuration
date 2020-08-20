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
 * Last modified: 2020.07.13 at 13:48
 */

declare(strict_types=1);


namespace Neunerlei\ConfigTests\Unit;


use Closure;
use FilesystemIterator;
use Neunerlei\ConfigTests\LoaderTestTrait;
use Neunerlei\ConfigTests\TestHelperTrait;
use Neunerlei\Configuration\Exception\InvalidLocationException;
use Neunerlei\Configuration\Util\LocationIteratorTrait;
use PHPUnit\Framework\TestCase;

class LocationIteratorTest extends TestCase
{
    use TestHelperTrait;
    use LoaderTestTrait;
    
    public function provideTestIteratorGenerationData(): array
    {
        $baseDir = $this->getPackageRootPath() . 'Example/Plugins/';
        
        return [
            [
                $baseDir . '*',
                [$baseDir . 'plugin1', $baseDir . 'plugin2', $baseDir . 'plugin3'],
            ],
            [
                $baseDir,
                [rtrim($baseDir, '/')],
            ],
            [
                new FilesystemIterator($baseDir),
                [$baseDir . 'plugin1', $baseDir . 'plugin2', $baseDir . 'plugin3'],
            ],
        ];
    }
    
    /**
     * @dataProvider provideTestIteratorGenerationData
     *
     * @param          $globPatternOrIterator
     * @param   array  $expect
     */
    public function testIteratorGeneration($globPatternOrIterator, array $expect): void
    {
        $i      = $this->makeInstance();
        $result = $this->iteratorToArray($i($globPatternOrIterator));
        $this->assertEquals($expect, $result);
    }
    
    public function provideTestIteratorGenerationFailData(): array
    {
        return [
            [function () { }],
            [123],
            [true],
            [new \ArrayIterator([])],
        ];
    }
    
    /**
     * @dataProvider provideTestIteratorGenerationFailData
     *
     * @param $value
     */
    public function testIteratorGenerationFail($value): void
    {
        $this->expectException(InvalidLocationException::class);
        $i = $this->makeInstance();
        $i($value);
    }
    
    protected function makeInstance(): Closure
    {
        $mock = $this->getMockBuilder(LocationIteratorTrait::class)->getMockForTrait();
        
        return $this->makeCaller($mock, 'prepareLocationIterator');
    }
    
    protected function iteratorToArray(FilesystemIterator $it): array
    {
        $result = [];
        foreach ($it as $i) {
            $result[] = $i->getPathname();
        }
        
        return $result;
    }
}
