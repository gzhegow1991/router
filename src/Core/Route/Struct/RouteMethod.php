<?php

namespace Gzhegow\Router\Core\Route\Struct;

use Gzhegow\Lib\Lib;
use Gzhegow\Lib\Modules\Type\Ret;


class RouteMethod
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
     * @return static|Ret<static>
     */
    public static function from($from, ?array $fallback = null)
    {
        $ret = Ret::new();

        $instance = null
            ?? static::fromStatic($from)->orNull($ret)
            ?? static::fromHttpMethod($from)->orNull($ret)
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
    public static function fromHttpMethod($from, ?array $fallback = null)
    {
        if (! ($from instanceof HttpMethod)) {
            return Ret::throw(
                $fallback,
                [ 'The `from` should be instance of: ' . HttpMethod::class, $from ],
                [ __FILE__, __LINE__ ]
            );
        }

        $instance = new static();
        $instance->value = $from->getValue();

        return Ret::ok($fallback, $instance);
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

        if (! HttpMethod::fromString($from)->isOk([ &$httpMethodObject, &$ret ])) {
            return Ret::throw($fallback, $ret);
        }

        $instance = new static();
        $instance->value = $httpMethodObject->getValue();

        return Ret::ok($fallback, $instance);
    }


    public function getValue() : string
    {
        return $this->value;
    }
}
