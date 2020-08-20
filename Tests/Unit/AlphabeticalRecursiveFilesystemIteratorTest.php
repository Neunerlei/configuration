<?php
/*
 * Copyright 2020 LABOR.digital
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
 * Last modified: 2020.08.20 at 15:53
 */

declare(strict_types=1);


namespace Neunerlei\ConfigTests\Unit;


use ArrayIterator;
use FilesystemIterator;
use Neunerlei\ConfigTests\LoaderTestTrait;
use Neunerlei\ConfigTests\TestHelperTrait;
use Neunerlei\Configuration\Util\AlphabeticalRecursiveFilesystemIterator;
use Neunerlei\Configuration\Util\LocationIteratorTrait;
use PHPUnit\Framework\TestCase;
use RecursiveIteratorIterator;
use SplFileInfo;

class AlphabeticalRecursiveFilesystemIteratorTest extends TestCase
{
    use LoaderTestTrait;
    use TestHelperTrait;

    public function provideTestOrderingData(): array
    {
        $directory = $this->getFixturePath(__CLASS__);

        return [
            [$directory],
            [new FilesystemIterator($directory)],
        ];
    }

    /**
     * @param $input
     *
     * @dataProvider provideTestOrderingData
     */
    public function testOrdering($input)
    {
        $directory = $this->getFixturePath(__CLASS__);
        $iterator  = new AlphabeticalRecursiveFilesystemIterator($input);
        $result    = iterator_to_array(new RecursiveIteratorIterator($iterator), true);
        self::assertContainsOnlyInstancesOf(SplFileInfo::class, $result);
        self::assertEquals([
            $directory . 'test-a.php',
            $directory . 'test-b.php',
            $directory . 'directory/test-c.php',
            $directory . 'directory/test-d.php',
            $directory . 'directory/subDirectory/test-e.php',
        ], array_keys($result));
    }

    public function testWithNonFileystemIterator()
    {
        $iterator = new AlphabeticalRecursiveFilesystemIterator(
            new ArrayIterator(['foo', 'bar'])
        );
        $result   = iterator_to_array(new RecursiveIteratorIterator($iterator), true);
        self::assertEmpty($result);
    }

    public function testLoactionIteratorTraitInteraction()
    {
        $mock   = $this->getMockBuilder(LocationIteratorTrait::class)->getMockForTrait();
        $it     = new AlphabeticalRecursiveFilesystemIterator(new ArrayIterator(['foo', 'bar']));
        $caller = $this->makeCaller($mock, 'prepareLocationIterator');
        self::assertSame($it, $caller($it));
    }
}
