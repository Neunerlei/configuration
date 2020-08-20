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
 * Last modified: 2020.07.14 at 18:29
 */

declare(strict_types=1);


namespace Neunerlei\ConfigTests\Unit;


use Neunerlei\Configuration\Loader\ConfigContext;
use Neunerlei\Configuration\Loader\LoaderContext;
use Neunerlei\Configuration\State\ConfigState;
use PHPUnit\Framework\TestCase;

class ConfigContextTest extends TestCase
{
    public function testGetters()
    {
        $loaderContext              = new LoaderContext();
        $loaderContext->environment = 'test';
        $loaderContext->type        = 'testType';
        
        $configContext = new ConfigContext();
        $state         = new ConfigState([]);
        $configContext->initialize($loaderContext, $state);
        
        $this->assertSame($state, $configContext->getState());
        $this->assertSame($loaderContext, $configContext->getLoaderContext());
        $this->assertEquals('test', $configContext->getEnvironment());
        $this->assertEquals('testType', $configContext->getType());
        
        // Test the namespacing
        $this->assertEquals('LIMBO', $configContext->getNamespace());
        
        $configContext->runWithNamespace('test', function (ConfigContext $configContext) {
            $this->assertEquals('test', $configContext->getNamespace());
            
            // Test nesting
            $configContext->runWithNamespace('inner', function (ConfigContext $context) {
                $this->assertEquals('inner', $context->getNamespace());
            });
            
            $this->assertEquals('test', $configContext->getNamespace());
        });
        
        $this->assertEquals('LIMBO', $configContext->getNamespace());
    }
}
