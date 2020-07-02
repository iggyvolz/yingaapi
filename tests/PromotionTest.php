<?php
declare(strict_types=1);
namespace iggyvolz\yingaapi\tests;

use Attribute;
use ReflectionMethod;
use PHPUnit\Framework\TestCase;
use iggyvolz\yingaapi\ApiRunner;
use iggyvolz\yingaapi\Annotations\Promoted;
use iggyvolz\yingaapi\Annotations\ApiMethod;
use iggyvolz\ClassProperties\ClassProperties;
use iggyvolz\yingaapi\Annotations\ThisResolver;
use iggyvolz\ClassProperties\Attributes\Property;
use iggyvolz\yingaapi\DependencyInjection\DependencyInjectionContext;

class PromotionTest extends TestCase
{
    private ApiRunner $runner;
    private DependencyInjectionContext $context;
    public function setUp():void
    {
        $this->runner = new ApiRunner([PromotionTest__DummyType::class]);
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

class PromotionTest__DummyType extends ClassProperties
{
    <<Property>> private string $name;
    <<ApiMethod("SetMyName")>>
    <<PromotionTest__DummyResolver>>
    public function setMyName(<<Promoted>> string $name):string
    {
        return $this->name;
    }
    <<ApiMethod("SetMyNameAlt")>>
    <<PromotionTest__DummyResolver>>
    public function setMyNameAlt(<<Promoted("name")>> string $myName):string
    {
        return $this->name;
    }
}

<<Attribute(Attribute::TARGET_METHOD)>>
class PromotionTest__DummyResolver extends ThisResolver
{
    public function getThis(ReflectionMethod $method, array $data):object
    {
        return new PromotionTest__DummyType;
    }
}