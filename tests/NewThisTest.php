<?php
declare(strict_types=1);
namespace iggyvolz\yingaapi\tests;

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
        $this->runner = new ApiRunner([NewThisTest__DummyType::class]);
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
}

class NewThisTest__DummyType extends ClassProperties
{
    private function __construct(<<ReadOnlyProperty>> private string $name) {}
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
}