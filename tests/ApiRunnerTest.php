<?php
declare(strict_types=1);
namespace iggyvolz\yingaapi\tests;

use LogicException;
use PHPUnit\Framework\TestCase;
use iggyvolz\yingaapi\ApiRunner;
use iggyvolz\yingaapi\Annotations\ApiMethod;
use iggyvolz\yingaapi\DependencyInjection\Injected;
use iggyvolz\yingaapi\DependencyInjection\Injectable;
use iggyvolz\yingaapi\Exceptions\MissingParameterException;
use iggyvolz\yingaapi\Exceptions\MethodDoesNotExistException;
use iggyvolz\yingaapi\DependencyInjection\DependencyInjectionContext;
use iggyvolz\yingaapi\Exceptions\DependencyInjectionFailureException;

class ApiRunnerTest__NullInjectable implements Injectable
{
    public static function Get(DependencyInjectionContext $context):static
    {
        throw new DependencyInjectionFailureException("a");
    }
}

class ApiRunnerTest__NullInjectable2 implements Injectable
{
    public static function Get(DependencyInjectionContext $context):static
    {
        throw new DependencyInjectionFailureException("b");
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
                <<ApiMethod("TestDependencyInjection")>>
                public static function exampleDependencyInjection(<<Injected>> DependencyInjectionContext $foo):int
                {
                    return spl_object_id($foo);
                }
                public static function notAnApiMethod():int
                {
                    return 2;
                }
                <<ApiMethod("InvalidDependencyInjection")>>
                public static function invalidDependencyInjection(<<Injected>> $untyped):?bool
                {
                    return null;
                }
                <<ApiMethod("NullInjected")>>
                public static function nullInjected(<<Injected>> ?ApiRunnerTest__NullInjectable $ni):?int
                {
                    return is_null($ni) ? null : spl_object_id($ni);
                }
                <<ApiMethod("IllegalNullInjected")>>
                public static function illegalNullInjected(<<Injected>> ApiRunnerTest__NullInjectable $ni):int
                {
                    return spl_object_id($ni);
                }
                <<ApiMethod("OneOfMultiple")>>
                public static function injectOneOfMultiple(<<Injected>> ApiRunnerTest__NullInjectable|DependencyInjectionContext $ni):int
                {
                    return spl_object_id($ni);
                }
                <<ApiMethod("IllegalInjection")>>
                public static function illegalInjection(<<Injected>> int $foo):int
                {
                    return $foo;
                }
                <<ApiMethod("MultipleInjectionFailures")>>
                public static function multipleInjectionFailures(<<Injected>> ApiRunnerTest__NullInjectable|ApiRunnerTest__NullInjectable2 $ni):int
                {
                    return spl_object_id($ni);
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
    public function testDependencyInjection():void
    {
        $result = $this->runner->run("TestDependencyInjection", [], $this->context)->getResponse();
        $this->assertSame(spl_object_id($this->context), $result);
    }
    public function testNotAnApiMethod():void
    {
        $this->expectException(MethodDoesNotExistException::class);
        $this->runner->run("notAnApiMethod", [], $this->context)->getResponse();
    }
    public function testInvalidDependencyInjection():void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Cannot inject a non-typed variable");
        $this->runner->run("InvalidDependencyInjection", [], $this->context)->getResponse();
    }
    public function testNullInjected():void
    {
        $result = $this->runner->run("NullInjected", [], $this->context)->getResponse();
        $this->assertNull($result);
    }
    public function testIllegalNullInjected():void
    {
        $this->expectException(DependencyInjectionFailureException::class);
        $this->expectExceptionMessage("a");
        $result = $this->runner->run("IllegalNullInjected", [], $this->context)->getResponse();
    }
    public function testMultipleDependencyInjection():void
    {
        $result = $this->runner->run("OneOfMultiple", [], $this->context)->getResponse();
        $this->assertSame(spl_object_id($this->context), $result);
    }
    public function testIllegalInjectionType():void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Attempted to inject a non-injectable class");
        $result = $this->runner->run("IllegalInjection", [], $this->context)->getResponse();
    }
    public function testMultipleInjectionFailures():void
    {
        $this->expectException(DependencyInjectionFailureException::class);
        $this->expectExceptionMessage("a, b");
        $result = $this->runner->run("MultipleInjectionFailures", [], $this->context)->getResponse();
    }
}