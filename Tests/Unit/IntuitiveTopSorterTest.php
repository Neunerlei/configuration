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


use Neunerlei\Configuration\Exception\CyclicDependencyException;
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
                ['b', 'e', 'f', 'c', 'd', 'a', 'g'],
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
                ['e', 'f', 'c', 'd', 'a', 'g', 'b'],
            ],
            [
                12,
                ['a', 'b', 'c', 'd', 'e', 'f', 'g'],
                function (IntuitiveTopSorter $sorter) {
                    $sorter->moveItemBefore('a', 'e');
                    $sorter->moveItemBefore('b', 'e');
                    $sorter->moveItemBefore('c', 'e');
                    $sorter->moveItemBefore('f', 'b');
                    $sorter->moveItemBefore('g', 'f');
                },
                ['a', 'g', 'f', 'b', 'c', 'd', 'e'],
            ],
            [
                13,
                [
                    'core',
                    'api',
                    'imaging',
                    'routing',
                    'shib',
                    'be',
                    'element',
                    'module',
                    'fe',
                    'link',
                    'pid',
                    'pidSite',
                    'raw',
                    'routingSite',
                    'ct',
                    'tca',
                    'ts',
                    'preparse',
                ],
                function (IntuitiveTopSorter $sorter) {
                    $sorter->moveItemBefore('shib', 'element');
                    $sorter->moveItemAfter('element', 'tca');
                    $sorter->moveItemBefore('element', 'ts');
                    $sorter->moveItemBefore('module', 'ts');
                    $sorter->moveItemBefore('fe', 'ts');
                    $sorter->moveItemAfter('link', 'ts');
                    $sorter->moveItemAfter('link', 'pid');
                    $sorter->moveItemBefore('pid', 'ts');
                    $sorter->moveItemAfter('pidSite', 'pid');
                    $sorter->moveItemAfter('routingSite', 'routing');
                    $sorter->moveItemAfter('routingSite', 'pidSite');
                    $sorter->moveItemAfter('ct', 'tca');
                    $sorter->moveItemAfter('tca', 'core');
                    $sorter->moveItemAfter('preparse', 'ts');
                },
                [
                    'core',
                    'api',
                    'imaging',
                    'routing',
                    'shib',
                    'be',
                    'tca',
                    'element',
                    'module',
                    'fe',
                    'pid',
                    'pidSite',
                    'raw',
                    'routingSite',
                    'ct',
                    'ts',
                    'link',
                    'preparse',
                ],
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

    public function testCyclicDependencyException(): void
    {
        $this->expectException(CyclicDependencyException::class);
        $this->expectExceptionMessage('Found a cyclic dependency in: b -> c -> a -> b');

        (new IntuitiveTopSorter(['a', 'b', 'c']))
            ->moveItemAfter('a', 'b')
            ->moveItemAfter('b', 'c')
            ->moveItemAfter('c', 'a')
            ->sort();
    }
}
