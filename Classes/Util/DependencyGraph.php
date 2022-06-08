<?php
/*
 * Copyright 2022 LABOR.digital
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
 * Last modified: 2022.06.08 at 20:50
 */

declare(strict_types=1);


namespace Neunerlei\Configuration\Util;


use Neunerlei\Configuration\Exception\CyclicDependencyException;

class DependencyGraph
{
    /**
     * @var array
     */
    protected $order;

    protected $dependencies = [];

    public function __construct(array $order)
    {
        $this->order = $order;
    }

    public function doesAHaveDirectDependencyOnB(string $a, string $b): bool
    {
        return in_array($b, $this->order[$a], true);
    }

    public function doesAHaveTransitiveDependencyOnB(string $a, string $b): bool
    {
        return in_array($b, $this->resolveDependencies($a), true);
    }

    /**
     * Resolves the nested dependencies of the provided key
     *
     * @param   string  $key
     * @param   array   $path
     *
     * @return array
     */
    protected function resolveDependencies(string $key, array $path = []): array
    {
        if (isset($this->dependencies[$key])) {
            return $this->dependencies[$key];
        }

        $dependencies = [];

        foreach ($this->order[$key] ?? [] as $depKey) {
            if (in_array($depKey, $path, true)) {
                throw new CyclicDependencyException(
                    'Found a cyclic dependency in: ' . implode(' -> ', $path) . ' -> ' . $depKey
                );
            }

            $dependencies[] = [$depKey];
            $dependencies[] = $this->resolveDependencies($depKey, array_merge($path, [$depKey]));
        }

        return $this->dependencies[$key] = array_unique(array_merge(...$dependencies));
    }
}
