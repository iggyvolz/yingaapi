<?php
declare(strict_types=1);
namespace iggyvolz\yingaapi;

use Throwable;
use LogicException;
use ReflectionType;
use ReflectionClass;
use ReflectionMethod;
use RuntimeException;
use ReflectionAttribute;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionUnionType;
use iggyvolz\yingaapi\ApiResponse;
use iggyvolz\ClassProperties\Identifiable;
use iggyvolz\yingaapi\Annotations\ApiMethod;
use iggyvolz\ClassProperties\ClassProperties;
use iggyvolz\yingaapi\DependencyInjection\Injected;
use iggyvolz\yingaapi\DependencyInjection\Injectable;
use iggyvolz\ClassProperties\Attributes\ReadOnlyProperty;
use iggyvolz\yingaapi\Exceptions\InvalidParameterException;
use iggyvolz\yingaapi\Exceptions\MissingParameterException;
use iggyvolz\yingaapi\Exceptions\MethodDoesNotExistException;
use iggyvolz\yingaapi\DependencyInjection\DependencyInjectionContext;
use iggyvolz\yingaapi\Exceptions\DependencyInjectionFailureException;

class ApiRunner extends ClassProperties
{
    /**
     * @var ReflectionMethod[]
     */
    <<ReadOnlyProperty>> private array $methods;
    /**
     * @var string[] $classes
     * @psalm-var class-string[] $classes
     */
    public function __construct(array $classes)
    {
        $classes = array_map(fn(string $s) => new ReflectionClass($s), $classes);
        $methods = array_merge(...array_map(fn(ReflectionClass $refl):array => $refl->getMethods(), $classes));
        // Filter only methods with ApiMethod annotation
        $methods = array_values(array_filter($methods, function(ReflectionMethod $method):bool {
            foreach($method->getAttributes() as $attribute) {
                if($attribute->newInstance() instanceof ApiMethod) {
                    return true;
                }
            }
            return false;
        }));
        $this->methods = array_combine(array_map(fn(ReflectionMethod $m) => $m->getAttributes(
            ApiMethod::class, ReflectionAttribute::IS_INSTANCEOF
        )[0]->newInstance()->name, $methods), $methods);
    }

    /**
     * @return (string|null)[]
     */
    private static function transformType(?ReflectionType $type):array
    {
        return array_unique(iterator_to_array((function() use($type){
            if($type instanceof ReflectionType && $type->allowsNull()) {
                yield null;
            }
            if($type instanceof ReflectionNamedType) {
                yield $type->getName();
            } elseif($type instanceof ReflectionUnionType) {
                foreach($type->getTypes() as $t) {
                    yield from self::transformType($t);
                }
            } else {
                throw new LogicException("Cannot inject a non-typed variable");
            }
        })(), false), SORT_REGULAR);
    }

    private static function getParameterValue(ReflectionParameter $param, array $args, DependencyInjectionContext $context):bool|int|float|string|array|object|null
    {
        $name = $param->getName();
        $type = self::transformType($param->getType());
        if(!empty($param->getAttributes(Injected::class, ReflectionAttribute::IS_INSTANCEOF))) {
            $failures = []; // Collect DependencyInjectionFailureExceptions
            foreach($type as $t) {
                if(is_null($t)) continue;
                if(!is_subclass_of($t, Injectable::class)) {
                    throw new LogicException("Attempted to inject a non-injectable class");
                }
                try {
                    return $t::Get($context);
                } catch(DependencyInjectionFailureException $e) {
                    $failures[] = $e;
                }
            }
            // We couldn't get the injected value
            if(in_array(null, $type)) {
                // This is okay, return null
                return null;
            }
            // This is not okay, need to exit with a DependencyInjectionFailureException
            if(count($failures) === 1) {
                throw $failures[0];
            }
            // Need to merge messages of $failures
            $failures = array_map(fn(DependencyInjectionFailureException $ex) => $ex->getMessage(), $failures);
            $failures = array_values(array_filter($failures, fn(string $msg):bool => !empty($msg)));
            $failures = implode(", ", $failures);
            throw new DependencyInjectionFailureException($failures);
        } elseif(array_key_exists($name, $args)) {
            foreach($type as $t) {
                if(is_null($t)) {
                    if(is_null($args[$name])) {
                        // Passed null for a null type, this is okay
                        return null;
                    } else {
                        // Nullable type, but we didn't pass null - try next type
                        continue;
                    }
                }
                // Check if the type matches
                if(get_debug_type($args[$name]) === $t && in_array($t, ["int", "string", "bool", "float"])) {
                    return $args[$name];
                }
                // Exception - if we are expecting a float and pass an int
                if(is_int($args[$name]) && $t === "float") {
                    return $args[$name];
                }
                if(is_subclass_of($t, Identifiable::class) && (is_int($args[$name]) || is_string($args[$name]))) {
                    $val = $t::getFromIdentifier($args[$name]);
                    if(!is_null($val)) {
                        return $val;
                    }
                }
            }
            throw new InvalidParameterException($name);
        } else {
            throw new MissingParameterException($name);
        }
    }

    public function run(string $method, array $data, DependencyInjectionContext $context):ApiResponse
    {
        try {
            if(!array_key_exists($method, $this->methods)) {
                throw new MethodDoesNotExistException($method);
            }
            $method = $this->methods[$method];
            if(!$method->isStatic()) {
                // @codeCoverageIgnoreStart
                // Not yet implemented
                throw new RuntimeException("Not implemented");
                // @codeCoverageIgnoreEnd
            }
            $method->setAccessible(true);
            $args = [];
            foreach($method->getParameters() as $param) {
                $args[] = self::getParameterValue($param, $data, $context);
            }
            $response = $method->invokeArgs(null, $args);
            return new ApiResponse($response);
        } catch(Throwable $e) {
            return new ApiResponse($e);
        }
    }
}