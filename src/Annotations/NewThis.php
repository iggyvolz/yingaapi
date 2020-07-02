<?php
declare(strict_types=1);
namespace iggyvolz\yingaapi\Annotations;

use Attribute;
use LogicException;
use ReflectionMethod;
use ReflectionParameter;
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
    /**
     * @param array<string,string> $cparams Array of constructor param names to method param names to be used
     */
    public function __construct(private array $cparams=[]){}
    public function getThis(ReflectionMethod $method, array $data):object
    {
        $class = $method->getDeclaringClass();
        $className = $class->getName();
        $constructor = $class->getConstructor();
        if(is_null($constructor)) {
            return $class->newInstance(); 
        }
        if(!$constructor->isPublic()) {
            throw new LogicException("Constructor is not public on $className");
        }
        $params = $constructor->getParameters();
        $args = [];
        foreach($params as $param) {
            // Use either the name passed to this constructor, or the name of the parameter
            $name = $this->cparams[$param->getName()] ?? $param->getName();
            if(array_key_exists($name, $data)) {
                $args[]=$data[$name];
            } elseif($param->isDefaultValueAvailable()) {
                $args[]=$param->getDefaultValue();
            } else {
                throw new LogicException("Parameter $name was not set on method");
            }
        }
        return $class->newInstance(...$args);
    }
}