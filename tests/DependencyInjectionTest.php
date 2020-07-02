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

class DependencyInjectionTest__NullInjectable implements Injectable
{
    public static function Get(DependencyInjectionContext $context):static
    {
        throw new DependencyInjectionFailureException("a");
    }
}

class DependencyInjectionTest__NullInjectable2 implements Injectable
{
    public static function Get(DependencyInjectionContext $context):static
    {
        throw new DependencyInjectionFailureException("b");
    }
}

class DependencyInjectionTest extends TestCase
{
    private ApiRunner $runner;
    private DependencyInjectionContext $context;
    public function setUp():void
    {
        $this->runner = new ApiRunner([
            get_class(new class
            {
                <<ApiMethod("TestDependencyInjection")>>
                public static function exampleDependencyInjection(<<Injected>> DependencyInjectionContext $foo):int
                {
                    return spl_object_id($foo);
                }
                <<ApiMethod("InvalidDependencyInjection")>>
                public static function invalidDependencyInjection(<<Injected>> $untyped):?bool
                {
                    return null;
                }
                <<ApiMethod("NullInjected")>>
                public static function nullInjected(<<Injected>> ?DependencyInjectionTest__NullInjectable $ni):?int
                {
                    return is_null($ni) ? null : spl_object_id($ni);
                }
                <<ApiMethod("IllegalNullInjected")>>
                public static function illegalNullInjected(<<Injected>> DependencyInjectionTest__NullInjectable $ni):int
                {
                    return spl_object_id($ni);
                }
                <<ApiMethod("OneOfMultiple")>>
                public static function injectOneOfMultiple(<<Injected>> DependencyInjectionTest__NullInjectable|DependencyInjectionContext $ni):int
                {
                    return spl_object_id($ni);
                }
                <<ApiMethod("IllegalInjection")>>
                public static function illegalInjection(<<Injected>> int $foo):int
                {
                    return $foo;
                }
                <<ApiMethod("MultipleInjectionFailures")>>
                public static function multipleInjectionFailures(<<Injected>> DependencyInjectionTest__NullInjectable|DependencyInjectionTest__NullInjectable2 $ni):int
                {
                    return spl_object_id($ni);
                }
            })
        ]);
        $this->context = new DependencyInjectionContext;
    }
    public function testDependencyInjection():void
    {
        $result = $this->runner->run("TestDependencyInjection", [], $this->context)->getResponse();
        $this->assertSame(spl_object_id($this->context), $result);
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