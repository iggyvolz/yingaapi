<?php
declare(strict_types=1);
namespace iggyvolz\yingaapi\DependencyInjection;

use iggyvolz\yingaapi\DependencyInjection\Injectable;

class DependencyInjectionContext implements Injectable
{
    public static function Get(DependencyInjectionContext $context):static
    {
        return $context;
    }
}