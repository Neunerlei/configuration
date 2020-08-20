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
     * @var Iterator
     */
    protected $wrappedIterator;

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
            $this->wrappedIterator = $path;
        } else {
            parent::__construct($path, $flags);
            $this->wrappedIterator = new FilesystemIterator($path, $flags);
        }

    }

    /**
     * @inheritDoc
     */
    public function valid()
    {
        $innerIterator = $this->getInnerIterator();

        return $innerIterator->valid()
               && $innerIterator->current() instanceof SplFileInfo;
    }

    /**
     * @inheritDoc
     */
    public function rewind()
    {
        $this->getInnerIterator()->rewind();
    }

    /**
     * @inheritDoc
     */
    public function next()
    {
        $this->getInnerIterator()->next();
    }

    /**
     * @inheritDoc
     */
    public function key()
    {
        return $this->getInnerIterator()->key();
    }

    /**
     * @inheritDoc
     */
    public function current()
    {
        return $this->getInnerIterator()->current();
    }

    /**
     * @inheritDoc
     */
    public function setInfoClass($class_name = null)
    {
        parent::setInfoClass($class_name);
        $this->infoClass = $class_name;
        if (method_exists($this->wrappedIterator, 'setInfoClass')) {
            $this->wrappedIterator->setInfoClass($class_name);
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
        $it = new self($this->current()->getRealPath(), $this->getFlags());
        $it->setInfoClass($this->infoClass);

        return $it;
    }

    /**
     * Creates the inner iterator only when it is needed by the outer iterator.
     * This allows the setInfoClass() to trigger before the wrapper instance is created
     *
     * @return \Iterator
     * @throws \Neunerlei\Arrays\ArrayException
     */
    public function getInnerIterator(): Iterator
    {
        if (isset($this->innerIterator)) {
            return $this->innerIterator;
        }

        if (! $this->wrappedIterator instanceof FilesystemIterator) {
            return $this->innerIterator = $this->wrappedIterator;
        }

        $files = $folders = [];
        foreach ($this->wrappedIterator as $file) {
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

        return $this->innerIterator = $children;
    }
}
