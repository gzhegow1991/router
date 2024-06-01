<?php

namespace Gzhegow\Router\Route\Struct;

use Gzhegow\Router\Router;
use Gzhegow\Router\Exception\LogicException;
use function Gzhegow\Router\_err;
use function Gzhegow\Router\_php_dump;
use function Gzhegow\Router\_filter_path;


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
        if (null === ($path = _filter_path($string))) {
            return _err([ 'The `from` should be valid path', $string ]);
        }

        if (0 !== strpos($path, '/')) {
            return _err([ 'The `from` should start with `/` sign', $string ]);
        }

        $allowed = ''
            . 'A-Za-z0-9'
            . '_.~'
            . preg_quote('/', '/')
            . preg_quote(Router::PATTERN_ENCLOSURE, '/')
            . '-';

        if (preg_match("/[^{$allowed}]/", $path)) {
            $regex = "/[{$allowed}]+/";

            return _err([ 'The `from` should match the regex: ' . $regex, $string ]);
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
