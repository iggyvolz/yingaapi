<?php
declare(strict_types=1);
namespace iggyvolz\yingaapi\Annotations;

use Attribute;
use LogicException;
use ReflectionMethod;
use iggyvolz\ClassProperties\Identifiable;
use iggyvolz\yingaapi\Exceptions\InvalidParameterException;
use iggyvolz\yingaapi\Exceptions\MissingParameterException;

/**
 * Constructs $this from the class's constructor
 * Optionally takes an array of parameters to set for constructor (defaults to the same name)
 */
<<Attribute(Attribute::TARGET_METHOD)>>
class NewThis extends ThisResolver
{
    public function __construct(private array $cparams=[]){}
    public function getThis(ReflectionMethod $method, array $data):object
    {
        throw new LogicException("Not yet implemented");
    }
}