<?php

namespace Gzhegow\Router\Handler;

use Gzhegow\Router\Exception\LogicException;
use function Gzhegow\Router\_err;
use function Gzhegow\Router\_php_dump;
use function Gzhegow\Router\_filter_string;
use function Gzhegow\Router\_php_method_exists;


abstract class GenericHandler implements \Serializable, \JsonSerializable
{
    /**
     * @var string
     */
    public $key;

    /**
     * @var \Closure
     */
    public $closure;

    /**
     * @var array{
     *     0: object|class-string,
     *     1: string
     * }
     */
    public $method;
    /**
     * @var class-string
     */
    public $methodClass;
    /**
     * @var object
     */
    public $methodObject;
    /**
     * @var string
     */
    public $methodName;

    /**
     * @var callable|object|class-string
     */
    public $invokable;
    /**
     * @var callable|object
     */
    public $invokableObject;
    /**
     * @var class-string
     */
    public $invokableClass;

    /**
     * @var callable|string
     */
    public $function;


    /**
     * @return static
     */
    public static function from($from) : object
    {
        if (null === ($instance = static::tryFrom($from))) {
            throw new LogicException([
                'Unknown `from`: ' . _php_dump($from),
            ]);
        }

        return $instance;
    }

    /**
     * @return static|null
     */
    public static function tryFrom($from) : ?object
    {
        $instance = null
            ?? static::fromStatic($from)
            ?? static::fromClosure($from)
            ?? static::fromMethod($from)
            ?? static::fromInvokable($from)
            ?? static::fromFunction($from);

        return $instance;
    }


    /**
     * @return static|null
     */
    protected static function fromStatic($static) : ?object
    {
        if (! is_a($static, static::class)) {
            return _err([ 'The `from` should be instance of: ' . static::class, $static ]);
        }

        return $static;
    }

    /**
     * @return static|null
     */
    protected static function fromClosure($closure) : ?object
    {
        if (! is_a($closure, \Closure::class)) {
            return _err([ 'The `from` should be instance of: ' . \Closure::class, $closure ]);
        }

        $instance = new static();
        $instance->key = static::key(_php_dump($closure));
        $instance->closure = $closure;

        return $instance;
    }

    /**
     * @return static|null
     */
    protected static function fromMethod($method) : ?object
    {
        if (! _php_method_exists($method, null, $methodArray)) {
            return _err([ 'The `from` should be existing method', $method ]);
        }

        $instance = new static();

        $instance->method = $methodArray;
        $instance->methodName = $methodArray[ 1 ];

        $isObject = is_object($methodArray[ 0 ]);

        if ($isObject) {
            $key = [ _php_dump($methodArray[ 0 ]), $methodArray[ 1 ] ];

            $instance->methodObject = $methodArray[ 0 ];

        } else {
            $key = $methodArray;

            $instance->methodClass = $methodArray[ 0 ];
        }

        $instance->key = static::key($key);

        return $instance;
    }

    /**
     * @return static|null
     */
    protected static function fromInvokable($invokable) : ?object
    {
        $instance = null;

        if (is_object($invokable)) {
            if (! is_callable($invokable)) {
                return null;
            }

            $instance = new static();
            $instance->key = static::key(_php_dump($invokable));
            $instance->invokable = $invokable;
            $instance->invokableObject = $invokable;

        } elseif (null !== ($_invokableClass = _filter_string($invokable))) {
            if (! class_exists($_invokableClass)) {
                return null;
            }

            if (! method_exists($_invokableClass, '__invoke')) {
                return null;
            }

            $instance = new static();
            $instance->key = static::key($_invokableClass);
            $instance->invokable = $_invokableClass;
            $instance->invokableClass = $_invokableClass;
        }

        if (null === $instance) {
            return _err([ 'The `from` should be existing invokable class or object', $invokable ]);
        }

        return $instance;
    }

    /**
     * @return static|null
     */
    protected static function fromFunction($function) : ?object
    {
        $_function = _filter_string($function);

        if (! function_exists($_function)) {
            return _err([ 'The `from` should be existing function name', $function ]);
        }

        $instance = new static();
        $instance->key = static::key($_function);
        $instance->function = $_function;

        return $instance;
    }


    public function getKey() : string
    {
        return $this->key;
    }

    protected static function key($key) : string
    {
        return serialize($key);
    }


    public function __serialize() : array
    {
        $vars = get_object_vars($this);

        return array_filter($vars);
    }

    public function __unserialize(array $data) : void
    {
        foreach ( $data as $key => $val ) {
            $this->{$key} = $val;
        }
    }

    public function serialize()
    {
        $array = $this->__serialize();

        return serialize($array);
    }

    public function unserialize($data)
    {
        $array = unserialize($data);

        $this->__unserialize($array);
    }

    public function jsonSerialize()
    {
        return $this->__serialize();
    }
}
