<?php

namespace Gzhegow\Router\Route\Struct;

use Gzhegow\Router\Lib;
use Gzhegow\Router\Router;
use Gzhegow\Router\Exception\LogicException;


class Path
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
        if (null === ($path = Lib::filter_path($string))) {
            return Lib::php_trigger_error([ 'The `from` should be valid path', $string ]);
        }

        if (0 !== strpos($path, '/')) {
            return Lib::php_trigger_error([ 'The `from` should start with `/` sign', $string ]);
        }

        $allowed = ''
            . 'A-Za-z0-9'
            . '_.~'
            . preg_quote('/', '/')
            . preg_quote(Router::PATTERN_ENCLOSURE, '/')
            . '-';

        if (preg_match("/[^{$allowed}]/", $path)) {
            $regex = "/[{$allowed}]+/";

            return Lib::php_trigger_error([ 'The `from` should match the regex: ' . $regex, $string ]);
        }

        $instance = new static();
        $instance->value = $path;

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
