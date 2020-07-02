<?php
declare(strict_types=1);
namespace iggyvolz\yingaapi\Exceptions;

use Exception;

class MissingParameterException extends Exception
{
    public function __construct(string $parameter) {
        parent::__construct("Missing parameter $parameter");
    }
}