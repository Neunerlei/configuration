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
 * Last modified: 2020.08.20 at 16:38
 */

declare(strict_types=1);


namespace Neunerlei\Configuration\Util;


use AppendIterator;
use Iterator;

class FilesystemAppendIterator extends AppendIterator
{

    /**
     * A list of iterators that where appended
     *
     * @var Iterator[]
     */
    protected $innerIterators = [];

    /**
     * @inheritDoc
     */
    public function append(Iterator $iterator)
    {
        $this->innerIterators[] = $iterator;
        parent::append($iterator);
    }

    /**
     * Used to set the file information object on all child iterators
     *
     * @param   string|null  $class_name  The name to use for the file information object
     */
    public function setInfoClass(?string $class_name = null): void
    {
        foreach ($this->innerIterators as $iterator) {
            if (method_exists($iterator, 'setInfoClass')) {
                $iterator->setInfoClass($class_name);
            }
        }
    }
}
