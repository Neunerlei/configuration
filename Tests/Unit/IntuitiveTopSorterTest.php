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
 * Last modified: 2020.07.13 at 20:18
 */

declare(strict_types=1);


namespace Neunerlei\ConfigTests\Unit;


use Neunerlei\Configuration\Util\IntuitiveTopSorter;
use PHPUnit\Framework\TestCase;

class IntuitiveTopSorterTest extends TestCase
{
    
    public function provideTestSortingData(): array
    {
        return [
            [
                1,
                ['a', 'b', 'c', 'd', 'e', 'f', 'g'],
                function (IntuitiveTopSorter $sorter) {
                    $sorter->moveItemAfter('a', 'd');
                },
                ['b', 'c', 'd', 'a', 'e', 'f', 'g'],
            ],
            [
                2,
                ['a', 'b', 'c', 'd', 'e', 'f', 'g'],
                function (IntuitiveTopSorter $sorter) {
                    $sorter->moveItemAfter('a', 'd');
                    $sorter->moveItemAfter('a', 'e');
                },
                ['b', 'c', 'd', 'e', 'a', 'f', 'g'],
            ],
            [
                3,
                ['a', 'b', 'c', 'd', 'e', 'f', 'g'],
                function (IntuitiveTopSorter $sorter) {
                    $sorter->moveItemAfter('a', 'd');
                    $sorter->moveItemAfter('d', 'e');
                },
                ['b', 'c', 'e', 'd', 'a', 'f', 'g'],
            ],
            [
                4,
                ['a', 'b', 'c', 'd', 'e', 'f', 'g'],
                function (IntuitiveTopSorter $sorter) {
                    $sorter->moveItemAfter('a', 'd');
                    $sorter->moveItemAfter('a', 'c');
                    $sorter->moveItemAfter('d', 'e');
                },
                ['b', 'c', 'e', 'd', 'a', 'f', 'g'],
            ],
            [
                5,
                ['a', 'b', 'c', 'd', 'e', 'f', 'g'],
                function (IntuitiveTopSorter $sorter) {
                    $sorter->moveItemBefore('g', 'a');
                },
                ['g', 'a', 'b', 'c', 'd', 'e', 'f'],
            ],
            [
                6,
                ['a', 'b', 'c'],
                function (IntuitiveTopSorter $sorter) {
                    $sorter->moveItemAfter('a', 'c');
                },
                ['b', 'c', 'a'],
            ],
            [
                7,
                ['a', 'b', 'c'],
                function (IntuitiveTopSorter $sorter) {
                    $sorter->moveItemBefore('c', 'a');
                },
                ['c', 'a', 'b'],
            ],
            [
                8,
                ['a', 'b', 'c'],
                function (IntuitiveTopSorter $sorter) {
                    $sorter->moveItemBefore('c', 'x');
                    $sorter->moveItemAfter('c', 'x');
                },
                ['a', 'b', 'c'],
            ],
            [
                9,
                ['a', 'b', 'c'],
                function (IntuitiveTopSorter $sorter) {
                    $sorter->moveItemBefore('x', 'a');
                },
                ['a', 'b', 'c'],
            ],
            [
                10,
                ['a', 'b', 'c', 'd', 'e', 'f', 'g'],
                function (IntuitiveTopSorter $sorter) {
                    $sorter->moveItemAfter('a', 'e');
                    $sorter->moveItemBefore('e', 'c');
                    $sorter->moveItemAfter('c', 'f');
                },
                ['b', 'd', 'f', 'e', 'a', 'c', 'g'],
            ],
            [
                11,
                ['a', 'b', 'c', 'd', 'e', 'f', 'g'],
                function (IntuitiveTopSorter $sorter) {
                    $sorter->moveItemAfter('a', 'e');
                    $sorter->moveItemBefore('e', 'c');
                    $sorter->moveItemAfter('c', 'f');
                    $sorter->moveItemAfter('b', 'g');
                },
                ['d', 'f', 'e', 'a', 'c', 'g', 'b'],
            ],
        ];
        
    }
    
    /**
     * @param   int       $i
     * @param   array     $list
     * @param   callable  $modifier
     * @param   array     $expect
     *
     * @dataProvider provideTestSortingData
     */
    public function testSorting(int $i, array $list, callable $modifier, array $expect)
    {
        $GLOBALS['I'] = $i;
        $sorter       = new IntuitiveTopSorter($list);
        $modifier($sorter);
        $this->assertEquals($expect, $sorter->sort(), $i . ' failed');
    }
}
