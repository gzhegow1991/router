<?php

namespace Gzhegow\Router\Core\Route\Struct;

use Gzhegow\Lib\Lib;
use Gzhegow\Router\Router;
use Gzhegow\Lib\Modules\Php\Result\Ret;
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
     * @param Ret $ret
     *
     * @return static|bool|null
     */
    public static function from($from, $ret = null)
    {
        $retCur = Result::asValue();

        $instance = null
            ?? static::fromStatic($from, $retCur)
            ?? static::fromString($from, $retCur);

        if ($retCur->isErr()) {
            return Result::err($ret, $retCur);
        }

        return Result::ok($ret, $instance);
    }

    /**
     * @param Ret $ret
     *
     * @return static|bool|null
     */
    public static function fromStatic($from, $ret = null)
    {
        if ($from instanceof static) {
            return Result::ok($ret, $from);
        }

        return Result::err(
            $ret,
            [ 'The `from` should be instance of: ' . static::class, $from ],
            [ __FILE__, __LINE__ ]
        );
    }

    /**
     * @param Ret $ret
     *
     * @return static|bool|null
     */
    public static function fromString($from, $ret = null)
    {
        if (! Lib::type()->path($fromPath, $from)) {
            return Result::err(
                $ret,
                [ 'The `from` should be valid path', $from ],
                [ __FILE__, __LINE__ ]
            );
        }

        if (0 !== strpos($fromPath, '/')) {
            return Result::err(
                $ret,
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
                $ret,
                [ 'The `from` should match the regex: ' . $regex, $from ],
                [ __FILE__, __LINE__ ]
            );
        }

        $instance = new static();
        $instance->value = $fromPath;

        return Result::ok($ret, $instance);
    }


    public function getValue() : string
    {
        return $this->value;
    }
}
