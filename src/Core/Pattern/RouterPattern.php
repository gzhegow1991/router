<?php

namespace Gzhegow\Router\Core\Pattern;

use Gzhegow\Lib\Lib;
use Gzhegow\Router\Router;
use Gzhegow\Lib\Modules\Type\Ret;


class RouterPattern implements \Serializable
{
    /**
     * @var string
     */
    public $pattern;
    /**
     * @var string
     */
    public $attribute;
    /**
     * @var string
     */
    public $regex;


    private function __construct()
    {
    }


    public function __serialize() : array
    {
        $vars = get_object_vars($this);

        return array_filter($vars);
    }

    public function __unserialize(array $data)
    {
        foreach ( $data as $key => $val ) {
            $this->{$key} = $val;
        }
    }

    public function serialize()
    {
        $array = $this->__serialize();

        return serialize($array);
    }

    public function unserialize($data)
    {
        $array = unserialize($data);

        $this->__unserialize($array);
    }


    /**
     * @return static|Ret<static>
     */
    public static function from($from, ?array $fallback = null)
    {
        $ret = Ret::new();

        $instance = null
            ?? static::fromStatic($from)->orNull($ret)
            ?? static::fromArray($from)->orNull($ret);

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
    public static function fromArray($from, ?array $fallback = null)
    {
        $theType = Lib::type();

        if (! is_array($from)) {
            return Ret::throw(
                $fallback,
                [ 'The `from` should be array', $from ],
                [ __FILE__, __LINE__ ]
            );
        }

        [ $pattern, $regex ] = $from + [ null, null ];

        if (! $theType->string_not_empty($pattern)->isOk([ &$patternStringNotEmpty, &$ret ])) {
            return Ret::throw($fallback, $ret);
        }

        if (! $theType->string_not_empty($regex)->isOk([ &$regexStringNotEmpty, &$ret ])) {
            return Ret::throw($fallback, $ret);
        }

        if (! (true
            && (Router::PATTERN_ENCLOSURE[ 0 ] === $patternStringNotEmpty[ 0 ])
            && (Router::PATTERN_ENCLOSURE[ 1 ] === substr($patternStringNotEmpty, -1))
        )) {
            return Ret::throw(
                $fallback,
                [
                    ''
                    . 'The `from[0]` should be wrapped with signs: '
                    . '[ ' . Router::PATTERN_ENCLOSURE[ 0 ] . ' ]'
                    . '[ ' . Router::PATTERN_ENCLOSURE[ 1 ] . ' ]',
                    //
                    $from,
                ],
                [ __FILE__, __LINE__ ]
            );
        }

        $attributeString = substr($patternStringNotEmpty, 1, -1);

        if (! preg_match($regexp = '/[a-z][a-z0-9_]*/', $attributeString)) {
            return Ret::throw(
                $fallback,
                [
                    ''
                    . 'The `from[0]` should match regex: '
                    . '[ ' . $regexp . ' ][ ' . $attributeString . ' ]',
                    //
                    $from,
                ],
                [ __FILE__, __LINE__ ]
            );
        }

        if ($isContainSlashes = (false !== strpos($regexStringNotEmpty, '/'))) {
            return Ret::throw(
                $fallback,
                [ 'The `from[1]` should not contain slash symbols', $from ],
                [ __FILE__, __LINE__ ]
            );
        }

        if ($isContainNamedGroups = preg_match('/\(\?P\<[^\>]+\>/', $regexStringNotEmpty)) {
            return Ret::throw(
                $fallback,
                [ 'The `from[1]` should not contain named groups', $from ],
                [ __FILE__, __LINE__ ]
            );
        }

        if (! $theType->regex($regexp = "/{$regexStringNotEmpty}/")->isOk([ 1 => &$ret ])) {
            return Ret::throw($fallback, $ret);
        }

        $regexString = "(?<{$attributeString}>{$regexStringNotEmpty})";

        $instance = new static();
        $instance->pattern = $patternStringNotEmpty;
        $instance->attribute = $attributeString;
        $instance->regex = $regexString;

        return Ret::ok($fallback, $instance);
    }
}
