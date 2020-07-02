<?php
declare(strict_types=1);
namespace iggyvolz\yingaapi\Exceptions;

use Exception;

class InvalidParameterException extends Exception
{
    public function __construct(string $parameter) {
        parent::__construct("Invalid parameter $parameter");
    }
}