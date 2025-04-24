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
    protected $value;


    private function __construct()
    {
    }


    public function __toString()
    {
        return $this->value;
    }


    /**
     * @return static|bool|null
     */
    public static function from($from, array $refs = [])
    {
        $withErrors = array_key_exists(0, $refs);

        $refs[ 0 ] = $refs[ 0 ] ?? null;

        $instance = null
            ?? static::fromStatic($from, $refs)
            ?? static::fromString($from, $refs);

        if (! $withErrors) {
            if (null === $instance) {
                throw $refs[ 0 ];
            }
        }

        return $instance;
    }

    /**
     * @return static|bool|null
     */
    public static function fromStatic($from, array $refs = [])
    {
        if ($from instanceof static) {
            return Lib::refsResult($refs, $from);
        }

        return Lib::refsError(
            $refs,
            new LogicException(
                [ 'The `from` should be instance of: ' . static::class, $from ]
            )
        );
    }

    /**
     * @return static|bool|null
     */
    public static function fromString($from, array $refs = [])
    {
        if (! Lib::type()->path($fromPath, $from)) {
            return Lib::refsError(
                $refs,
                new LogicException(
                    [ 'The `from` should be valid path', $from ]
                )
            );
        }

        if (0 !== strpos($fromPath, '/')) {
            return Lib::refsError(
                $refs,
                new LogicException(
                    [ 'The `from` should start with `/` sign', $from ]
                )
            );
        }

        $allowed = ''
            . 'A-Za-z0-9'
            . '_.~'
            . preg_quote('/', '/')
            . preg_quote(Router::PATTERN_ENCLOSURE, '/')
            . '-';

        if (preg_match("/[^{$allowed}]/", $fromPath)) {
            $regex = "/[{$allowed}]+/";

            return Lib::refsError(
                $refs,
                new LogicException(
                    [ 'The `from` should match the regex: ' . $regex, $from ]
                )
            );
        }

        $instance = new static();
        $instance->value = $fromPath;

        return Lib::refsResult($refs, $instance);
    }


    public function getValue() : string
    {
        return $this->value;
    }
}
