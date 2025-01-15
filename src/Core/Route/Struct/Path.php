<?php

namespace Gzhegow\Router\Core\Route\Struct;

use Gzhegow\Lib\Lib;
use Gzhegow\Router\Core\Router;
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
    public static function from($from) // : static
    {
        $instance = static::tryFrom($from, $error);

        if (null === $instance) {
            throw $error;
        }

        return $instance;
    }

    /**
     * @return static|null
     */
    public static function tryFrom($from, \Throwable &$last = null) // : ?static
    {
        $last = null;

        Lib::php()->errors_start($b);

        $instance = null
            ?? static::tryFromInstance($from)
            ?? static::tryFromString($from);

        $errors = Lib::php()->errors_end($b);

        if (null === $instance) {
            foreach ( $errors as $error ) {
                $last = new LogicException($error, $last);
            }
        }

        return $instance;
    }


    /**
     * @return static|null
     */
    public static function tryFromInstance($instance) // : ?static
    {
        if (! is_a($instance, static::class)) {
            return Lib::php()->error(
                [ 'The `from` should be instance of: ' . static::class, $instance ]
            );
        }

        return $instance;
    }

    /**
     * @return static|null
     */
    public static function tryFromString($string) // : ?static
    {
        if (null === ($path = Lib::parse()->path($string))) {
            return Lib::php()->error(
                [ 'The `from` should be valid path', $string ]
            );
        }

        if (0 !== strpos($path, '/')) {
            return Lib::php()->error(
                [ 'The `from` should start with `/` sign', $string ]
            );
        }

        $allowed = ''
            . 'A-Za-z0-9'
            . '_.~'
            . preg_quote('/', '/')
            . preg_quote(Router::PATTERN_ENCLOSURE, '/')
            . '-';

        if (preg_match("/[^{$allowed}]/", $path)) {
            $regex = "/[{$allowed}]+/";

            return Lib::php()->error(
                [ 'The `from` should match the regex: ' . $regex, $string ]
            );
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
