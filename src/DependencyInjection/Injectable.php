<?php
declare(strict_types=1);
namespace iggyvolz\yingaapi\DependencyInjection;

interface Injectable
{
    static function Get(DependencyInjectionContext $context):static;
}