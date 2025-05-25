<?php

namespace Gzhegow\Router\Core\Pattern;

use Gzhegow\Lib\Lib;
use Gzhegow\Router\Router;
use Gzhegow\Lib\Modules\Php\Result\Ret;
use Gzhegow\Lib\Modules\Php\Result\Result;


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
     * @param Ret $ret
     *
     * @return static|bool|null
     */
    public static function from($from, $ret = null)
    {
        $retCur = Result::asValue();

        $instance = null
            ?? static::fromStatic($from, $retCur)
            ?? static::fromArray($from, $retCur);

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
    public static function fromArray($from, $ret = null)
    {
        if (! is_array($from)) {
            return Result::err(
                $ret,
                [ 'The `from` should be array', $from ],
                [ __FILE__, __LINE__ ]
            );
        }

        [ $pattern, $regex ] = $from + [ null, null ];

        if (! Lib::type()->string_not_empty($patternString, $pattern)) {
            return Result::err(
                $ret,
                [ 'The `from[0]` should be non-empty string', $from ],
                [ __FILE__, __LINE__ ]
            );
        }

        if (! Lib::type()->string_not_empty($regexString, $regex)) {
            return Result::err(
                $ret,
                [ 'The `from[1]` should be non-empty string', $from ],
                [ __FILE__, __LINE__ ]
            );
        }

        if (! (true
            && (Router::PATTERN_ENCLOSURE[ 0 ] === $patternString[ 0 ])
            && (Router::PATTERN_ENCLOSURE[ 1 ] === substr($patternString, -1))
        )) {
            return Result::err(
                $ret,
                [
                    ''
                    . 'The `from[0]` should be wrapped with signs: '
                    . '`' . Router::PATTERN_ENCLOSURE[ 0 ] . '`'
                    . ' `' . Router::PATTERN_ENCLOSURE[ 1 ] . '`',
                    //
                    $from,
                ],
                [ __FILE__, __LINE__ ]
            );
        }

        $attributeString = substr($patternString, 1, -1);

        if (! preg_match($regexp = '/[a-z][a-z0-9_]*/', $attributeString)) {
            return Result::err(
                $ret,
                [
                    ''
                    . 'The `from[0]` should match regex: '
                    . $regexp . ' / ' . $attributeString,
                    //
                    $from,
                ],
                [ __FILE__, __LINE__ ]
            );
        }

        $symbols = [ '/', '|', '(', ')' ];

        $forbidden = implode('', $symbols);
        $forbidden = preg_quote($forbidden, '/');
        if (preg_match("/[{$forbidden}]/", $regexString)) {
            return Result::err(
                $ret,
                [
                    ''
                    . 'The `from[1]` should not contain symbols: '
                    . '[ ' . implode(' ][ ', $symbols) . ' ]',
                    //
                    $from,
                ],
                [ __FILE__, __LINE__ ]
            );
        }

        if (! Lib::type()->regex($var, $regexp = "/{$regexString}/")) {
            return Result::err(
                $ret,
                [ 'The `from[1]` caused invalid regex: ' . $regexp, $from ],
                [ __FILE__, __LINE__ ]
            );
        }

        $regexString = "(?<{$attributeString}>{$regexString})";

        $instance = new static();
        $instance->pattern = $patternString;
        $instance->attribute = $attributeString;
        $instance->regex = $regexString;

        return Result::ok($ret, $instance);
    }
}
