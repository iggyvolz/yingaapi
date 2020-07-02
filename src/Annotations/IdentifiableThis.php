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
 * Gets $this from the identifier passed in $data["this"]
 */
<<Attribute>>
class IdentifiableThis extends ThisResolver
{
    public function getThis(ReflectionMethod $method, array $data):object
    {
        // Check that we are in an Identifiable class
        $class = $method->getDeclaringClass();
        if(!$class->isSubclassOf(Identifiable::class)) {
            throw new LogicException("Cannot declare IdentifiableThis on non-identifiable class ".static::class);
        }
        $className = $class->getName();
        if(!array_key_exists("this", $data)) {
            throw new MissingParameterException("this");
        }
        $thisIdent = $data["this"];
        if(!is_int($thisIdent) && !is_string($thisIdent)) {
            throw new InvalidParameterException("this");
        }
        $obj = $className::getFromIdentifier($thisIdent);
        if(is_null($obj)) {
            throw new InvalidParameterException("this");
        } else {
            return $obj;
        }
    }
}