<?php

namespace Gzhegow\Router\Core\Route\Struct;

use Gzhegow\Lib\Lib;
use Gzhegow\Lib\Modules\Php\Result\Result;


class RoutePath
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
    public static function from($from, $ret = null)
    {
        $retCur = Result::asValue();

        $instance = null
            ?? static::fromStatic($from, $retCur)
            ?? static::fromHttpPath($from, $retCur)
            ?? static::fromString($from, $retCur);

        if ($retCur->isErr()) {
            return Result::err($ret, $retCur);
        }

        return Result::ok($ret, $instance);
    }

    /**
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
     * @return static|bool|null
     */
    public static function fromHttpPath($from, $ret = null)
    {
        if (! ($from instanceof HttpPath)) {
            return Result::err(
                $ret,
                [ 'The `from` should be instance of: ' . HttpPath::class, $from ],
                [ __FILE__, __LINE__ ]
            );
        }

        if ($from->hasQuery()) {
            return Result::err(
                $ret,
                [ 'The `from` should be HttpPath without query string', $from ],
                [ __FILE__, __LINE__ ]
            );
        }

        if ($from->hasFragment()) {
            return Result::err(
                $ret,
                [ 'The `from` should be HttpPath without hash fragment', $from ],
                [ __FILE__, __LINE__ ]
            );
        }

        $instance = new static();
        $instance->value = $from->getValue();

        return Result::ok($ret, $instance);
    }

    /**
     * @return static|bool|null
     */
    public static function fromString($from, $ret = null)
    {
        if (! Lib::type()->string_not_empty($fromString, $from)) {
            return Result::err(
                $ret,
                [ 'The `from` should be non-empty string', $from ],
                [ __FILE__, __LINE__ ]
            );
        }

        $retCur = Result::asValueFalse();

        $httpMethod = HttpPath::fromString($from, $retCur);

        if ($retCur->isErr()) {
            return Result::err($ret, $retCur);
        }

        $instance = new static();
        $instance->value = $httpMethod->getPath();

        return Result::ok($ret, $instance);
    }


    public function getValue() : string
    {
        return $this->value;
    }
}
