<?php
declare(strict_types=1);
namespace iggyvolz\yingaapi\tests;

use LogicException;
use RuntimeException;
use PHPUnit\Framework\TestCase;
use iggyvolz\yingaapi\ApiRunner;
use iggyvolz\ClassProperties\Identifiable;
use iggyvolz\yingaapi\Annotations\ApiMethod;
use iggyvolz\ClassProperties\Attributes\Identifier;
use iggyvolz\yingaapi\DependencyInjection\Injected;
use iggyvolz\yingaapi\DependencyInjection\Injectable;
use iggyvolz\ClassProperties\Attributes\ReadOnlyProperty;
use iggyvolz\yingaapi\Exceptions\InvalidParameterException;
use iggyvolz\yingaapi\Exceptions\MissingParameterException;
use iggyvolz\yingaapi\Exceptions\MethodDoesNotExistException;
use iggyvolz\yingaapi\DependencyInjection\DependencyInjectionContext;

class ApiRunnerTest__Identifiable extends Identifiable
{
    private function __construct(<<Identifier>> <<ReadOnlyProperty>> private string $s) {}
    public static function getFromIdentifier($identifier): ?self
    {
        if(is_string($identifier)) {
            return new self($identifier);
        } else {
            return null;
        }
    }
}


class ApiRunnerTest extends TestCase
{
    private ApiRunner $runner;
    private DependencyInjectionContext $context;
    public function setUp():void
    {
        $this->runner = new ApiRunner([
            get_class(new class
            {
                <<ApiMethod("MyTestMethod")>>
                public static function exampleApiMethod():int
                {
                    return 2;
                }
                <<ApiMethod("MyAdder")>>
                public static function exampleApiMethodWithArg(int $foo):int
                {
                    return $foo+1;
                }
                public static function notAnApiMethod():int
                {
                    return 2;
                }
                <<ApiMethod("NullParameterTest")>>
                public static function nullParameterTest(?int $i):?int
                {
                    return $i;
                }
                <<ApiMethod("FloatMethod")>>
                public static function floatMethod(float $f):float
                {
                    return $f;
                }
                <<ApiMethod("IdentifiableMethod")>>
                public static function identifiableTest(ApiRunnerTest__Identifiable $thing):string
                {
                    return $thing->s;
                }
            })
        ]);
        $this->context = new DependencyInjectionContext;
    }
    public function testFakeApiMethod():void
    {
        $this->expectException(MethodDoesNotExistException::class);
        $this->expectExceptionMessage("FakeMethod does not exist");
        $this->runner->run("FakeMethod", [], $this->context)->getResponse();
    }
    public function testRealApiMethod():void
    {
        $result = $this->runner->run("MyTestMethod", [], $this->context)->getResponse();
        $this->assertSame(2, $result);
    }
    public function testAdder():void
    {
        $result = $this->runner->run("MyAdder", ["bar" => 34, "foo" => 22], $this->context)->getResponse();
        $this->assertSame(23, $result);
    }
    public function testAdderResponse():void
    {
        $result = $this->runner->run("MyAdder", ["bar" => 34, "foo" => 22], $this->context)->__toString();
        $this->assertSame('{"success":true,"response":23}', $result);
    }
    public function testAdderWithoutParams():void
    {
        $this->expectException(MissingParameterException::class);
        $this->expectExceptionMessage("Missing parameter foo");
        $result = $this->runner->run("MyAdder", [], $this->context)->getResponse();
    }
    public function testAdderWithoutParamsResponse():void
    {
        $result = $this->runner->run("MyAdder", [], $this->context)->__toString();
        $this->assertSame('{"success":false,"error":{"type":"iggyvolz\\\\yingaapi\\\\Exceptions\\\\MissingParameterException","message":"Missing parameter foo"}}', $result);
    }
    public function testNotAnApiMethod():void
    {
        $this->expectException(MethodDoesNotExistException::class);
        $this->runner->run("notAnApiMethod", [], $this->context)->getResponse();
    }
    public function testNullParameter():void
    {
        $result = $this->runner->run("NullParameterTest", ["i" => null], $this->context)->getResponse();
        $this->assertNull($result);
    }
    public function testNullableParameter():void
    {
        $result = $this->runner->run("NullParameterTest", ["i" => 7], $this->context)->getResponse();
        $this->assertSame(7, $result);
    }
    public function testIntToFloatPromotion():void
    {
        $result = $this->runner->run("FloatMethod", ["f" => 7], $this->context)->getResponse();
        $this->assertEquals(7, $result);
    }
    public function testIdentifiableMethod():void
    {
        $result = $this->runner->run("IdentifiableMethod", ["thing" => "foo"], $this->context)->getResponse();
        $this->assertEquals("foo", $result);
    }
    public function testInvalidParameter():void
    {
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage("Invalid parameter thing");
        $result = $this->runner->run("IdentifiableMethod", ["thing" => 7], $this->context)->getResponse();
    }
}