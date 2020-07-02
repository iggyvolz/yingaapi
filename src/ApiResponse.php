<?php
declare(strict_types=1);
namespace iggyvolz\yingaapi;

use Throwable;
use Stringable;
use JsonSerializable;
use iggyvolz\ClassProperties\ClassProperties;
use iggyvolz\ClassProperties\Attributes\ReadOnlyProperty;

class ApiResponse extends ClassProperties implements JsonSerializable, Stringable
{
    public function __construct(<<ReadOnlyProperty>> private $response) {}
    public function jsonSerialize():array
    {
        if(!($this->response instanceof Throwable)) {
            return [
                "success" => true,
                "response" => $this->response
            ];
        } else {
            return [
                "success" => false,
                "error" => [
                    "type" => get_class($this->response),
                    "message" => $this->response->getMessage(),
                ]
            ];
        }
    }
    public function __toString():string
    {
        return json_encode($this, JSON_THROW_ON_ERROR);
    }

    /**
     * Utility function for tests - throws an exception rather than wrapping it
     */
    public function getResponse()
    {
        if($this->response instanceof Throwable) {
            throw $this->response;
        }
        return $this->response;
    }
}