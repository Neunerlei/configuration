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
 * Last modified: 2020.07.13 at 14:33
 */

declare(strict_types=1);


namespace Neunerlei\ConfigTests\Unit;


use Neunerlei\ConfigTests\Fixture\FixtureContextAwareClass;
use Neunerlei\Configuration\Loader\ConfigContext;
use Neunerlei\Configuration\Loader\LoaderContext;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use RuntimeException;

class LoaderContextTest extends TestCase
{
    
    public function testGetInstance()
    {
        $i                = new LoaderContext();
        $i->configContext = new ConfigContext();
        
        // Instantiate without help
        $instance = $i->getInstance(FixtureContextAwareClass::class);
        $this->assertInstanceOf(FixtureContextAwareClass::class, $instance);
        $this->assertSame($i->configContext, $instance->getContext());
        
        // Instantiate with given callback
        $executed = false;
        $instance = $i->getInstance(FixtureContextAwareClass::class, function (string $classname) use (&$executed) {
            $this->assertEquals(FixtureContextAwareClass::class, $classname);
            $executed = true;
            
            return new FixtureContextAwareClass();
        });
        $this->assertInstanceOf(FixtureContextAwareClass::class, $instance);
        $this->assertSame($i->configContext, $instance->getContext());
        $this->assertTrue($executed);
        
        // Instantiate using a container
        $i->container = new class implements ContainerInterface
        {
            public $getCalled = false;
            public $hasCalled = false;
            
            public function get($id)
            {
                if ($id === FixtureContextAwareClass::class) {
                    $this->getCalled = true;
                    
                    return new FixtureContextAwareClass();
                }
                throw new RuntimeException('Not the correct class!');
            }
            
            public function has($id)
            {
                if ($id === FixtureContextAwareClass::class) {
                    $this->hasCalled = true;
                    
                    return true;
                }
                throw new RuntimeException('Not the correct class!');
            }
            
        };
        $instance     = $i->getInstance(FixtureContextAwareClass::class);
        $this->assertInstanceOf(FixtureContextAwareClass::class, $instance);
        $this->assertSame($i->configContext, $instance->getContext());
        $this->assertTrue($i->container->getCalled);
        $this->assertTrue($i->container->hasCalled);
        
        // The container wins, even if we give a callback
        $i->container->getCalled = false;
        $i->container->hasCalled = false;
        $instance                = $i->getInstance(FixtureContextAwareClass::class, function () {
            $this->fail('Ended up in the callback, where I should not go');
        });
        $this->assertInstanceOf(FixtureContextAwareClass::class, $instance);
        $this->assertSame($i->configContext, $instance->getContext());
        $this->assertTrue($i->container->getCalled);
        $this->assertTrue($i->container->hasCalled);
        
        // Use the fallback if the container does not know our class
        $i->container = new class implements ContainerInterface
        {
            public function get($id)
            {
                throw new RuntimeException('Get called!');
            }
            
            public function has($id)
            {
                return false;
            }
            
        };
        $executed     = false;
        $instance     = $i->getInstance(FixtureContextAwareClass::class, function (string $classname) use (&$executed) {
            $this->assertEquals(FixtureContextAwareClass::class, $classname);
            $executed = true;
            
            return new FixtureContextAwareClass();
        });
        $this->assertInstanceOf(FixtureContextAwareClass::class, $instance);
        $this->assertSame($i->configContext, $instance->getContext());
        $this->assertTrue($executed);
        
    }
    
    public function testDispatchEvent()
    {
        $i = new LoaderContext();
        
        // Nothing should happen if we don't have a dispatcher
        $i->dispatchEvent(new class
        {
        });
        
        
        // The dispatcher should get our event
        $i->eventDispatcher = new class implements EventDispatcherInterface
        {
            public $dispatched = false;
            
            public function dispatch(object $event)
            {
                if ($event instanceof FixtureContextAwareClass) {
                    $this->dispatched = true;
                    
                    return;
                }
                throw new RuntimeException('Not the correct class!');
            }
        };
        
        $i->dispatchEvent(new FixtureContextAwareClass());
        $this->assertTrue($i->eventDispatcher->dispatched);
    }
}
