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
 * Last modified: 2020.07.08 at 12:28
 */

declare(strict_types=1);


namespace Neunerlei\ConfigExample\Project\Handler\ContentElement;


class ExampleContentElementConfigurator
{
    /**
     * The namespace of the content element
     *
     * @var string
     */
    protected $namespace;
    
    /**
     * The unique content element key
     *
     * @var string
     */
    protected $key;
    
    /**
     * The title of the content element
     *
     * @var string
     */
    protected $title;
    
    /**
     * Options for the content element
     *
     * @var array
     */
    protected $options = [];
    
    /**
     * ExampleContentElementConfigurator constructor.
     *
     * @param   string  $key
     */
    public function __construct(string $namespace, string $key)
    {
        $this->key       = $key;
        $this->namespace = $namespace;
    }
    
    /**
     * Sets the title of the content element
     *
     * @param   string  $title
     *
     * @return $this
     */
    public function setTitle(string $title): self
    {
        $this->title = $title;
        
        return $this;
    }
    
    /**
     * Sets an option for the content element
     *
     * @param   string  $key
     * @param           $value
     *
     * @return $this
     */
    public function setOption(string $key, $value): self
    {
        $this->options[$key] = $value;
        
        return $this;
    }
    
    /**
     * Returns the finished config object
     *
     * @return array
     * @internal
     */
    public function finish(): array
    {
        return get_object_vars($this);
    }
}
