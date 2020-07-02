<?php
declare(strict_types=1);
namespace iggyvolz\yingaapi\tests;

use LogicException;
use PHPUnit\Framework\TestCase;
use iggyvolz\yingaapi\ApiRunner;
use iggyvolz\yingaapi\Annotations\NewThis;
use iggyvolz\yingaapi\Annotations\ApiMethod;
use iggyvolz\ClassProperties\ClassProperties;
use iggyvolz\ClassProperties\Attributes\ReadOnlyProperty;
use iggyvolz\yingaapi\DependencyInjection\DependencyInjectionContext;

class NewThisTest extends TestCase
{
    private ApiRunner $runner;
    private DependencyInjectionContext $context;
    public function setUp():void
    {
        $this->runner = new ApiRunner([
            NewThisTest__DummyType::class,
            NewThisTest__NoConstructorType::class,
            NewThisTest__PrivateConstructorType::class,
            NewThisTest__DefaultValueConstructorType::class,
        ]);
        $this->context = new DependencyInjectionContext;
    }
    public function testSetMyName():void
    {
        $result = $this->runner->run("SetMyName", ["name" => "Charlie"], $this->context)->getResponse();
        $this->assertSame("Charlie", $result);
    }
    public function testSetMyNameAlt():void
    {
        $result = $this->runner->run("SetMyNameAlt", ["myName" => "Charlie"], $this->context)->getResponse();
        $this->assertSame("Charlie", $result);
    }
    public function testMissingName():void
    {
        $this->expectException(LogicException::class);
        $result = $this->runner->run("MissingName", [], $this->context)->getResponse();
    }
    public function testNoConstructor():void
    {
        $result = $this->runner->run("NoConstructor", [], $this->context)->getResponse();
        $this->assertSame(47, $result);
    }
    public function testPrivateConstructor():void
    {
        $this->expectException(LogicException::class);
        $result = $this->runner->run("PrivateConstructor", [], $this->context)->getResponse();
    }
    public function testDefaultValueConstructor():void
    {
        $result = $this->runner->run("DefaultValueConstructor", [], $this->context)->getResponse();
        $this->assertSame(47, $result);
    }
    public function testDefaultValueConstructorPassedAnyways():void
    {
        $result = $this->runner->run("DefaultValueConstructorPassedAnyways", ["x" => 2], $this->context)->getResponse();
        $this->assertSame(2, $result);
    }
}

class NewThisTest__DummyType extends ClassProperties
{
    public function __construct(<<ReadOnlyProperty>> private string $name) {}
    <<ApiMethod("SetMyName")>>
    <<NewThis>>
    public function setMyName(string $name):string
    {
        return $this->name;
    }
    <<ApiMethod("SetMyNameAlt")>>
    <<NewThis([
        "name" => "myName"
    ])>>
    public function setMyNameAlt(string $myName):string
    {
        return $this->name;
    }

    <<ApiMethod("MissingName")>>
    <<NewThis>>
    public function missingName():string
    {
        return $this->name;
    }
}

class NewThisTest__NoConstructorType extends ClassProperties
{
    <<ApiMethod("NoConstructor")>>
    <<NewThis>>
    public function NoConstructor():int
    {
        return 47;
    }
}

class NewThisTest__PrivateConstructorType extends ClassProperties
{
    private function __construct(){}
    <<ApiMethod("PrivateConstructor")>>
    <<NewThis>>
    public function PrivateConstructor():int
    {
        return 47;
    }
}

class NewThisTest__DefaultValueConstructorType extends ClassProperties
{
    public function __construct(private int $x=47){}
    <<ApiMethod("DefaultValueConstructor")>>
    <<NewThis>>
    public function DefaultValueConstructor():int
    {
        return $this->x;
    }
    <<ApiMethod("DefaultValueConstructorPassedAnyways")>>
    <<NewThis>>
    public function DefaultValueConstructorPassedAnyways(int $x):int
    {
        return $this->x;
    }
}