<?php
declare(strict_types=1);
namespace iggyvolz\yingaapi\Exceptions;

use Exception;

class MethodDoesNotExistException extends Exception
{
    public function __construct(string $methodName) {
        parent::__construct("$methodName does not exist");
    }
}