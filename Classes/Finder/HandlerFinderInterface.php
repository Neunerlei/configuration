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
 * Last modified: 2020.07.06 at 22:08
 */

declare(strict_types=1);

namespace Neunerlei\Configuration\Finder;

use Neunerlei\Configuration\Loader\LoaderContext;

interface HandlerFinderInterface
{
    /**
     * Traverses all registered root- and handler locations to find viable
     * classes that implement the ConfigHandlerInterface. It also instantiates the classes,
     * initializes them by reading their own configuration and applies overrides and dependency sorting.
     *
     * @param   LoaderContext  $loaderContext  The loader configuration to find
     *                                         the handlers for
     *
     * @return \Neunerlei\Configuration\Handler\HandlerDefinition[]
     */
    public function find(LoaderContext $loaderContext): array;
}
