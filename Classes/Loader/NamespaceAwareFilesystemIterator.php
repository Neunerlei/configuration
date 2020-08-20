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
 * Last modified: 2020.07.06 at 21:16
 */

declare(strict_types=1);


namespace Neunerlei\Configuration\Loader;


use FilesystemIterator;
use Iterator;

class NamespaceAwareFilesystemIterator implements Iterator
{
    /**
     * @var \FilesystemIterator
     */
    protected $innerIterator;
    
    /**
     * @var string|callable
     */
    protected $namespaceOrGenerator;
    
    /**
     * NamespaceAwareFilesystemIterator constructor.
     *
     * @param   \FilesystemIterator  $innerIterator
     * @param   string|callable      $namespaceOrGenerator
     */
    public function __construct(FilesystemIterator $innerIterator, $namespaceOrGenerator)
    {
        $this->innerIterator        = $innerIterator;
        $this->namespaceOrGenerator = $namespaceOrGenerator;
    }
    
    /**
     * @inheritDoc
     */
    public function current()
    {
        return new NamespaceAwareSplFileInfo(
            $this->innerIterator->current()->getPathname(),
            $this->namespaceOrGenerator
        );
    }
    
    /**
     * @inheritDoc
     */
    public function next(): void
    {
        $this->innerIterator->next();
    }
    
    /**
     * @inheritDoc
     */
    public function key()
    {
        return $this->innerIterator->key();
    }
    
    /**
     * @inheritDoc
     */
    public function valid(): bool
    {
        return $this->innerIterator->valid();
    }
    
    /**
     * @inheritDoc
     */
    public function rewind(): void
    {
        $this->innerIterator->rewind();
    }
    
}
