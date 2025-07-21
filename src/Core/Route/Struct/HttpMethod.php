<?php

namespace Gzhegow\Router\Core\Route\Struct;

use Gzhegow\Lib\Lib;
use Gzhegow\Lib\Modules\Type\Ret;


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
     * @return static|Ret<static>
     */
    public static function from($from, ?array $fallback = null)
    {
        $ret = Ret::new();

        $instance = null
            ?? static::fromStatic($from)->orNull($ret)
            ?? static::fromString($from)->orNull($ret);

        if ($ret->isFail()) {
            return Ret::throw($fallback, $ret);
        }

        return Ret::ok($fallback, $instance);
    }

    /**
     * @return static|Ret<static>
     */
    public static function fromStatic($from, ?array $fallback = null)
    {
        if ($from instanceof static) {
            return Ret::ok($fallback, $from);
        }

        return Ret::throw(
            $fallback,
            [ 'The `from` should be instance of: ' . static::class, $from ],
            [ __FILE__, __LINE__ ]
        );
    }

    /**
     * @return static|Ret<static>
     */
    public static function fromString($from, ?array $fallback = null)
    {
        $theType = Lib::type();

        if (! $theType->string_not_empty($from)->isOk([ &$fromString, &$ret ])) {
            return Ret::throw($fallback, $ret);
        }

        $fromString = strtoupper($fromString);

        if (! isset(static::LIST_METHOD[ $fromString ])) {
            return Ret::throw(
                $fallback,
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

        return Ret::ok($fallback, $instance);
    }


    public function getValue() : string
    {
        return $this->value;
    }
}
