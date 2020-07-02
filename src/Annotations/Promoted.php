<?php
declare(strict_types=1);
namespace iggyvolz\yingaapi\Annotations;

use Attribute;
use ReflectionMethod;

/**
 * Marks values as promoted to a property on the class
 */
<<Attribute(Attribute::TARGET_PARAMETER)>>
class Promoted
{
    public function __construct(private ?string $propertyName = null){}
    public function promote(object $self, string $parameterName, $value):void
    {
        $propertyName = $this->propertyName ?? $parameterName;
        $self->$propertyName = $value;
    }
}