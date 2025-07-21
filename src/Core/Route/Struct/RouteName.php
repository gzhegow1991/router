<?php

namespace Gzhegow\Router\Core\Route\Struct;

use Gzhegow\Lib\Lib;
use Gzhegow\Lib\Modules\Type\Ret;


class RouteName
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

        if (! $theType->trim($from)->isOk([ &$fromString, &$ret ])) {
            return Ret::throw($fallback, $ret);
        }

        if (false !== strpos($fromString, '!')) {
            return Ret::throw(
                $fallback,
                [ 'The `from` should not contain symbol `!`', $from ],
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
