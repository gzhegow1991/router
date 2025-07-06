<?php

namespace Gzhegow\Router\Core\Route\Struct;

use Gzhegow\Lib\Lib;
use Gzhegow\Lib\Modules\Php\Result\Result;


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
    public static function fromString($from, $ret = null)
    {
        if (! Lib::type()->string_not_empty($fromString, $from)) {
            return Result::err(
                $ret,
                [ 'The `from` should be non-empty string', $from ],
                [ __FILE__, __LINE__ ]
            );
        }

        $fromString = strtoupper($fromString);

        if (! isset(static::LIST_METHOD[ $fromString ])) {
            return Result::err(
                $ret,
                [
                    ''
                    . 'The `from` should be one of: '
                    . implode(',', array_keys(static::LIST_METHOD)),
                    //
                    $from,
                ],
                [ __FILE__, __LINE__ ]
            );
        }

        $instance = new static();
        $instance->value = $fromString;

        return Result::ok($ret, $instance);
    }


    public function getValue() : string
    {
        return $this->value;
    }
}
