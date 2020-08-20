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
 * Last modified: 2020.07.06 at 21:19
 */

declare(strict_types=1);


namespace Neunerlei\Configuration\Loader;


use SplFileInfo;

class NamespaceAwareSplFileInfo extends SplFileInfo
{
    
    /**
     * The namespace or namespace generator callable
     *
     * @var string|callable
     */
    protected $namespaceOrGenerator;
    
    /**
     * @inheritDoc
     */
    public function __construct($file_name, $namespaceOrGenerator)
    {
        parent::__construct($file_name);
        $this->namespaceOrGenerator = $namespaceOrGenerator;
    }
    
    /**
     * Returns the registered namespace or generator callback
     *
     * @return callable|string
     */
    public function getNamespaceOrGenerator()
    {
        return $this->namespaceOrGenerator;
    }
    
    /**
     * Returns the mapped namespace for this file
     *
     * @param   string  $className  The classname that should be passed to an potential generator function
     *
     * @return string
     */
    public function getNamespace(string $className): string
    {
        return is_string($this->namespaceOrGenerator)
            ? $this->namespaceOrGenerator
            : ($this->namespaceOrGenerator)($this, $className);
    }
    
}
