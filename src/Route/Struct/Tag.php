<?php

namespace Gzhegow\Router\Route\Struct;

use Gzhegow\Router\Lib;
use Gzhegow\Router\Exception\LogicException;


class Tag
{
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
                'Unknown `from`: ' . Lib::php_dump($from),
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
            return Lib::php_trigger_error([ 'The `from` should be instance of: ' . static::class, $static ]);
        }

        return $static;
    }

    /**
     * @return static|null
     */
    protected static function fromString($string) : ?object
    {
        if (null === ($tag = Lib::filter_string($string))) {
            return Lib::php_trigger_error([ 'The `from` should be non-empty string', $string ]);
        }

        $instance = new static();
        $instance->value = $tag;

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
