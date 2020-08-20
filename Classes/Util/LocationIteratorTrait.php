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
 * Last modified: 2020.07.06 at 18:40
 */

declare(strict_types=1);


namespace Neunerlei\Configuration\Util;


use FilesystemIterator;
use GlobIterator;
use Iterator;
use Neunerlei\Configuration\Exception\InvalidLocationException;

trait LocationIteratorTrait
{

    /**
     * Helper to initialize a glob pattern into a filesystem iterator if required
     *
     * @param $globPatternOrIterator
     *
     * @return \FilesystemIterator
     * @throws \Neunerlei\Configuration\Exception\InvalidLocationException
     */
    protected function prepareLocationIterator($globPatternOrIterator): Iterator
    {
        if ($globPatternOrIterator instanceof AlphabeticalRecursiveFilesystemIterator) {
            return $globPatternOrIterator;
        }

        if ($globPatternOrIterator instanceof FilesystemIterator) {
            return new AlphabeticalRecursiveFilesystemIterator($globPatternOrIterator);
        }

        if (is_string($globPatternOrIterator)) {
            return new AlphabeticalRecursiveFilesystemIterator(
                new GlobIterator(rtrim($globPatternOrIterator, '\\/'))
            );
        }

        throw new InvalidLocationException('The given location is invalid! Only strings or filesystem iterators are allowed!');
    }
}
