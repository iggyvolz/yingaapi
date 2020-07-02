<?php
declare(strict_types=1);
namespace iggyvolz\yingaapi\Exceptions;

use Exception;

/**
 * Notes that dependency injection failed
 * If null is acceptable, that should be used; otherwise this exception should be thrown
 */
class DependencyInjectionFailureException extends Exception
{
    public function __construct(string $message = "") {
        parent::__construct($message);
    }
}