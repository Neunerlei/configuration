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
 * Last modified: 2020.08.20 at 13:31
 */

declare(strict_types=1);


namespace Neunerlei\Configuration\Util;


use ArrayIterator;
use FilesystemIterator;
use Iterator;
use Neunerlei\Arrays\Arrays;
use RecursiveIterator;
use SplFileInfo;

class AlphabeticalRecursiveFilesystemIterator extends FilesystemIterator implements RecursiveIterator
{
    /**
     * The inner iterator instance if the path was an iterator, or null if the path was a string
     *
     * @var \Iterator
     */
    protected $innerIterator;

    /**
     * The info class to create for my children
     *
     * @var string
     */
    protected $infoClass = SplFileInfo::class;

    /**
     * AlphabeticalRecursiveFilesystemIterator constructor.
     *
     * @param   string|Iterator  $path
     * @param   int              $flags
     */
    public function __construct(
        $path,
        $flags = FilesystemIterator::KEY_AS_PATHNAME | FilesystemIterator::CURRENT_AS_FILEINFO
    ) {
        if ($path instanceof Iterator) {
            if (! $path instanceof self && $path instanceof FilesystemIterator) {
                $this->innerIterator = $this->makeInstanceOfMyself($path);
            } else {
                $this->innerIterator = $path;
            }
        } else {
            parent::__construct($path, $flags);
            $this->innerIterator = $this->makeInstanceOfMyself(new FilesystemIterator($path));
        }

    }

    /**
     * @inheritDoc
     */
    public function valid()
    {
        return $this->innerIterator->valid() && $this->innerIterator->current() instanceof SplFileInfo;
    }


    /**
     * @inheritDoc
     */
    public function rewind()
    {
        $this->innerIterator->rewind();
    }

    /**
     * @inheritDoc
     */
    public function next()
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
    public function current()
    {
        return $this->innerIterator->current();
    }

    /**
     * @inheritDoc
     */
    public function setInfoClass($class_name = null)
    {
        parent::setInfoClass($class_name);
        $this->infoClass = $class_name;
        if (method_exists($this->innerIterator, 'setInfoClass')) {
            $this->innerIterator->setInfoClass($class_name);
        }
    }

    /**
     * @inheritDoc
     */
    public function hasChildren()
    {
        return $this->current()->isDir();
    }

    /**
     * @inheritDoc
     */
    public function getChildren()
    {
        $it = new FilesystemIterator($this->current()->getRealPath());
        $it->setInfoClass($this->infoClass);

        return $this->makeInstanceOfMyself($it);
    }

    /**
     * Creates a new wrapper iterator for the given iterator.
     * The wrapper will contain all files ordered by their name inside a directory.
     *
     * @param   \Iterator  $it
     *
     * @return $this
     * @throws \Neunerlei\Arrays\ArrayException
     */
    protected function makeInstanceOfMyself(Iterator $it): self
    {
        // Sort both files and folders alphabetically
        $files = $folders = [];
        foreach ($it as $file) {
            if ($file->isDir()) {
                $folders[$file->getPathname()] = $file;
            } else {
                $files[$file->getPathname()] = $file;
            }
        }
        ksort($folders);
        ksort($files);

        // Make sure folders are handled after files
        $contents = Arrays::attach($files, $folders);

        // Inherit the info class
        $children = new self(new ArrayIterator($contents));
        $children->setInfoClass($this->infoClass);

        return $children;
    }
}
