<?php

namespace Gzhegow\Router\Route\Struct;

use Gzhegow\Router\Exception\LogicException;
use function Gzhegow\Router\_err;
use function Gzhegow\Router\_php_dump;
use function Gzhegow\Router\_filter_string;


class HttpMethod
{
    const METHOD_CONNECT = 'CONNECT';
    const METHOD_DELETE  = 'DELETE';
    const METHOD_GET     = 'GET';
    const METHOD_HEAD    = 'HEAD';
    const METHOD_OPTIONS = 'OPTIONS';
    const METHOD_PATCH   = 'PATCH';
    const METHOD_POST    = 'POST';
    const METHOD_PUT     = 'PUT';
    const METHOD_TRACE   = 'TRACE';

    const LIST_METHOD = [
        self::METHOD_CONNECT => true,
        self::METHOD_DELETE  => true,
        self::METHOD_GET     => true,
        self::METHOD_HEAD    => true,
        self::METHOD_OPTIONS => true,
        self::METHOD_PATCH   => true,
        self::METHOD_POST    => true,
        self::METHOD_PUT     => true,
        self::METHOD_TRACE   => true,
    ];


    /**
     * @var string
     */
    public $value;


    private function __construct()
    {
    }

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
            ?? static::fromString($from);

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
    protected static function fromString($string) : ?object
    {
        if (null === ($_string = _filter_string($string))) {
            return _err([ 'The `from` should be non-empty string', $string ]);
        }

        $_string = strtoupper($_string);

        if (! isset(static::LIST_METHOD[ $_string ])) {
            return _err([
                'The `from` should be one of: ' . implode(',', array_keys(static::LIST_METHOD)),
                $string,
            ]);
        }

        $instance = new static();
        $instance->value = $_string;

        return $instance;
    }


    public function __toString()
    {
        return $this->value;
    }


    public function getValue() : string
    {
        return $this->value;
    }
}
