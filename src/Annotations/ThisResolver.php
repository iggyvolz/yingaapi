<?php
declare(strict_types=1);
namespace iggyvolz\yingaapi\Annotations;

use ReflectionMethod;

/**
 * Resolves $this from passed values
 */
abstract class ThisResolver
{
    public abstract function getThis(ReflectionMethod $method, array $data):object;
}