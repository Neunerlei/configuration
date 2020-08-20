<?php
declare(strict_types=1);

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
 * Last modified: 2020.07.08 at 11:14
 */

use Neunerlei\ConfigExample\ExampleCacheImplementation;
use Neunerlei\ConfigExample\Project\Handler\RuntimeHandlerInterface;
use Neunerlei\Configuration\Finder\FilteredHandlerFinder;
use Neunerlei\Configuration\Loader\Loader;

include __DIR__ . '/../vendor/autoload.php';

$configLoader = new Loader('exampleConfig', 'dev');

/** ===============================================
 * Cacheable configuration
 * =============================================== */
// Register the root locations where to look for config classes and handlers
$configLoader->registerRootLocation(
    __DIR__ . '/Plugins/*',
    static function (SplFileInfo $fileInfo) {
        return ucfirst($fileInfo->getFilename());
    });
$configLoader->registerRootLocation(
    __DIR__ . '/Project/',
    'project');

// Register the handler locations relative to the root locations
$configLoader->registerHandlerLocation('Handler/**');

// Cache the stored config
$configLoader->setCache(new ExampleCacheImplementation());

// Optional: You can filter the handler classes by their implemented interfaces
// Here we make sure NO runtime handler will be executed
$configLoader->setHandlerFinder(new FilteredHandlerFinder([RuntimeHandlerInterface::class], []));

// Load the config
$config = $configLoader->load();

// Example: Every subsequent load will now use the cached value
$configCached = $configLoader->load();
echo PHP_EOL;
echo 'Does compiled config match the cached config?' . PHP_EOL;
print_r($config->getAll() == $configCached->getAll() ? 'TRUE' : 'FALSE');
unset($configCached);

echo PHP_EOL . PHP_EOL;
echo 'Cached Config:' . PHP_EOL;
print_r($config->getAll());

/** ===============================================
 * Optional: additional Runtime configuration
 * =============================================== */

// Here we do the opposite of the code above.
// Now we make sure ONLY runtime handlers are executed
$configLoader->setHandlerFinder(new FilteredHandlerFinder([], [RuntimeHandlerInterface::class]));

// Now we run the config loader as "runtime" loader, which means you can create instances or closures
// for your configuration, as the list of config classes will be executed every time
$runtimeConfig = $configLoader->load(true);

// Example: Every subsequent load will always execute the config classes,
// however, the list of config classes will be cached, and we will therefore save performance too.
$runtimeConfigCached = $configLoader->load(true);
echo PHP_EOL;
echo 'Does compiled config match the cached config?' . PHP_EOL;
print_r($runtimeConfig->getAll() == $runtimeConfigCached->getAll() ? 'TRUE' : 'FALSE');
unset($runtimeConfigCached);

// Now we merge both the config and the runtime config
$config = $config->mergeWith($runtimeConfig);

// Now you have the runtime instances as well as your cached configuration
echo PHP_EOL . PHP_EOL;
echo 'Final config:' . PHP_EOL;

print_r($config->getAll());
exit();
