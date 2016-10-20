<?php


namespace AurynConfigTest;

use Auryn\Injector;
use AurynConfig\InjectionParams;
use Mockery;

class InjectorTest extends BaseTestCase
{
    public function testDefineParams()
    {
        $shares = ['share1'];
        $aliases = ['interface' => 'class'];
        $delegates = ['className' => 'delegate'];
        $classParams = ['className' => [':paramName' => 'value']];
        $prepares = ['className' => 'prepareCallable'];
        $namedParams = ['paramName' => 'value'];
        
        $injectionParams = new InjectionParams(
            $shares,
            $aliases,
            $delegates,
            $classParams,
            $prepares,
            $namedParams
        );

        $mock = Mockery::mock('Auryn\Injector');
        $mock->shouldReceive('share')->withArgs(['share1']);
        $mock->shouldReceive('alias')->withArgs(['interface', 'class']);
        $mock->shouldReceive('delegate')->withArgs(['className', 'delegate']);
        $mock->shouldReceive('define')->withArgs(['className', [':paramName' => 'value']]);
        $mock->shouldReceive('prepare')->withArgs(['className', 'prepareCallable']);
        $mock->shouldReceive('defineParam')->withArgs(['paramName', 'value']);

        $injectionParams->addToInjector($mock);
    }

    public function testFromSharedObjects()
    {
        $injectionParams = InjectionParams::fromSharedObjects([
            'AurynParamTest\Foo' => FooImplementation::create(),
            'AurynParamTest\Bar' => Bar::create(),
        ]);
        
        $fn = function (Foo $foo, Bar $bar) {
            $this->assertInstanceOf('AurynParamTest\FooImplementation', $foo);
            $this->assertInstanceOf('AurynParamTest\Bar', $bar);
        };

        $injector = new Injector();
        $injectionParams->addToInjector($injector);
        $injector->execute($fn);
    }

    public function testMergeSharedObjects()
    {
        $injectionParams = new InjectionParams(
            [],
            ['AurynParamTest\Foo' => 'AurynParamTest\FooImplementation']
        );
        $injectionParams->mergeSharedObjects([
            'AurynParamTest\Foo' => FooImplementation::create(),
            'AurynParamTest\Quux' => QuuxImplementation::create()
        ]);
        
        $fn = function (Foo $foo, Quux $quux /*, Bar $bar */) {
            $this->assertInstanceOf('AurynParamTest\FooImplementation', $foo);
            $this->assertInstanceOf('AurynParamTest\Quux', $quux);
        };

        $injector = new Injector();
        $injectionParams->addToInjector($injector);
        $injector->execute($fn);
    }

    public function testMergeSharedObjectsCoverage()
    {
        $injectionParams = new InjectionParams();
        $injectionParams->mergeSharedObjects([
            'AurynParamTest\Quux' => QuuxImplementation::create(),
            
        ]);
        
        $fn = function (Quux $quux) {
            $this->assertInstanceOf('AurynParamTest\Quux', $quux);
        };
        
        $injector = new Injector();
        $injectionParams->addToInjector($injector);
        $injector->execute($fn);
    }
    
    public function testMergeSharedObjectsSharingImplementation()
    {
        $injectionParams = new InjectionParams();
        $injectionParams->mergeSharedObjects([
            'AurynParamTest\Bar' => Bar::create(),
        ]);

        $injector = new Injector();
        $injectionParams->addToInjector($injector);
    }
    
    public function testMergeSharedObjectsExistingSharedPreserved()
    {
        $injectionParams = new InjectionParams(
            [FooImplementation::create()],
            ['AurynParamTest\Foo' => 'AurynParamTest\FooImplementation']
        );
        $injectionParams->mergeSharedObjects([
        ]);

        $injector = new Injector();
        $injectionParams->addToInjector($injector);
        
        $fn = function (Foo $foo) {
            $this->assertInstanceOf('AurynParamTest\Foo', $foo);
        };

        $injector = new Injector();
        $injectionParams->addToInjector($injector);
        $injector->execute($fn);
    }

    public function testMergeSharedObjectsError()
    {
        $injectionParams = new InjectionParams();
        $this->setExpectedException('Auryn\InjectorException');
        $injectionParams->mergeSharedObjects([
            'AurynParamTest\Bar' => 'hello',
        ]);
    }

    public function testMergeSharedObjectsError2()
    {
        $injectionParams = new InjectionParams(
            [],
            ['AurynParamTest\Foo' => 'AurynParamTest\FooImplementation']
        );
        $this->setExpectedException('Auryn\InjectorException');
        $injectionParams->mergeSharedObjects([
            'AurynParamTest\Foo' => 'hello',
        ]);
    }
}
