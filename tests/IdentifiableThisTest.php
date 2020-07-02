<?php
declare(strict_types=1);
namespace iggyvolz\yingaapi\tests;

use LogicException;
use PHPUnit\Framework\TestCase;
use iggyvolz\yingaapi\ApiRunner;
use iggyvolz\ClassProperties\Identifiable;
use iggyvolz\yingaapi\Annotations\ApiMethod;
use iggyvolz\ClassProperties\Attributes\Identifier;
use iggyvolz\yingaapi\Annotations\IdentifiableThis;
use iggyvolz\ClassProperties\Attributes\ReadOnlyProperty;
use iggyvolz\yingaapi\Exceptions\InvalidParameterException;
use iggyvolz\yingaapi\Exceptions\MissingParameterException;
use iggyvolz\yingaapi\DependencyInjection\DependencyInjectionContext;

class IdentifiableThisTest extends TestCase
{
    private ApiRunner $runner;
    private DependencyInjectionContext $context;
    public function setUp():void
    {
        $this->runner = new ApiRunner([
            get_class(new class("") extends Identifiable
            {
                public function __construct(<<ReadOnlyProperty>> <<Identifier>> private string $name){}
                <<ApiMethod("GetMyName")>>
                <<IdentifiableThis>>
                public function getMyName():string
                {
                    return $this->name;
                }
                public static function getFromIdentifier($identifier): ?self
                {
                    if($identifier === "Charlie") {
                        return new self("Charlie");
                    }
                    return null;
                }
            }),
            get_class(new class
            {
                <<ApiMethod("Invalid")>>
                <<IdentifiableThis>>
                public function thisAintValid():string
                {
                    return "x";
                }
                <<ApiMethod("NoResolver")>>
                public function noResolver():string
                {
                    return "x";
                }
            })
        ]);
        $this->context = new DependencyInjectionContext;
    }
    public function testIdentifiableThis():void
    {
        $result = $this->runner->run("GetMyName", ["this" => "Charlie"], $this->context)->getResponse();
        $this->assertSame("Charlie", $result);
    }
    public function testInvalidIdentifiableThisDeclaration():void
    {
        $this->expectException(LogicException::class);
        $this->runner->run("Invalid", [], $this->context)->getResponse();
    }
    public function testIdentifiableThisNotFound():void
    {
        $this->expectException(InvalidParameterException::class);
        $result = $this->runner->run("GetMyName", ["this" => "Delta"], $this->context)->getResponse();
    }
    public function testIdentifiableThisWrongType():void
    {
        $this->expectException(InvalidParameterException::class);
        $result = $this->runner->run("GetMyName", ["this" => []], $this->context)->getResponse();
    }
    public function testIdentifiableThisNotPassed():void
    {
        $this->expectException(MissingParameterException::class);
        $result = $this->runner->run("GetMyName", [], $this->context)->getResponse();
    }
    public function testNoResolver():void
    {
        $this->expectException(LogicException::class);
        $result = $this->runner->run("NoResolver", [], $this->context)->getResponse();
    }
}