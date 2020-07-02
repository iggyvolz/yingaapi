<?php
declare(strict_types=1);
namespace iggyvolz\yingaapi\Annotations;
use Attribute;
use iggyvolz\ClassProperties\ClassProperties;
use iggyvolz\ClassProperties\Attributes\ReadOnlyProperty;
<<Attribute>>
class ApiMethod extends ClassProperties
{
    public function __construct(<<ReadOnlyProperty>> private string $name){}
}