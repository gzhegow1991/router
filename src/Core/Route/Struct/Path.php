<?php

namespace Gzhegow\Router\Core\Route\Struct;

use Gzhegow\Lib\Lib;
use Gzhegow\Router\Core\Router;
use Gzhegow\Lib\Modules\Php\Result\Result;


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
    public static function from($from, $ctx = null)
    {
        Result::parse($cur);

        $instance = null
            ?? static::fromStatic($from, $cur)
            ?? static::fromString($from, $cur);

        if ($cur->isErr()) {
            return Result::err($ctx, $cur);
        }

        return Result::ok($ctx, $instance);
    }

    /**
     * @return static|bool|null
     */
    public static function fromStatic($from, $ctx = null)
    {
        if ($from instanceof static) {
            return Result::ok($ctx, $from);
        }

        return Result::err(
            $ctx,
            [ 'The `from` should be instance of: ' . static::class, $from ],
            [ __FILE__, __LINE__ ]
        );
    }

    /**
     * @return static|bool|null
     */
    public static function fromString($from, $ctx = null)
    {
        if (! Lib::type()->path($fromPath, $from)) {
            return Result::err(
                $ctx,
                [ 'The `from` should be valid path', $from ],
                [ __FILE__, __LINE__ ]
            );
        }

        if (0 !== strpos($fromPath, '/')) {
            return Result::err(
                $ctx,
                [ 'The `from` should start with `/` sign', $from ],
                [ __FILE__, __LINE__ ]
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

            return Result::err(
                $ctx,
                [ 'The `from` should match the regex: ' . $regex, $from ],
                [ __FILE__, __LINE__ ]
            );
        }

        $instance = new static();
        $instance->value = $fromPath;

        return Result::ok($ctx, $instance);
    }


    public function getValue() : string
    {
        return $this->value;
    }
}
