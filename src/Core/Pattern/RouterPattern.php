<?php

namespace Gzhegow\Router\Core\Pattern;

use Gzhegow\Lib\Lib;
use Gzhegow\Router\Core\Router;
use Gzhegow\Router\Exception\LogicException;


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


    public function __serialize()
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
     * @return static|bool|null
     */
    public static function from($from, array $refs = [])
    {
        $withErrors = array_key_exists(0, $refs);

        $refs[ 0 ] = $refs[ 0 ] ?? null;

        $instance = null
            ?? static::fromStatic($from, $refs)
            ?? static::fromArray($from, $refs);

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
    public static function fromArray($from, array $refs = [])
    {
        if (! is_array($from)) {
            return Lib::refsError(
                $refs,
                new LogicException(
                    [ 'The `from` should be array', $from ]
                )
            );
        }

        [ $pattern, $regex ] = $from + [ null, null ];

        if (! Lib::type()->string_not_empty($patternString, $pattern)) {
            return Lib::refsError(
                $refs,
                new LogicException(
                    [ 'The `from[0]` should be non-empty string', $from ]
                )
            );
        }

        if (! Lib::type()->string_not_empty($regexString, $regex)) {
            return Lib::refsError(
                $refs,
                new LogicException(
                    [ 'The `from[1]` should be non-empty string', $from ]
                )
            );
        }

        if (! (true
            && (Router::PATTERN_ENCLOSURE[ 0 ] === $patternString[ 0 ])
            && (Router::PATTERN_ENCLOSURE[ 1 ] === substr($patternString, -1))
        )) {
            return Lib::refsError(
                $refs,
                new LogicException(
                    [
                        ''
                        . 'The `from[0]` should be wrapped with signs: '
                        . '`' . Router::PATTERN_ENCLOSURE[ 0 ] . '`'
                        . ' `' . Router::PATTERN_ENCLOSURE[ 1 ] . '`',
                        $from,
                    ]
                )
            );
        }

        $attributeString = substr($patternString, 1, -1);

        if (! preg_match($regexp = '/[a-z][a-z0-9_]*/', $attributeString)) {
            return Lib::refsError(
                $refs,
                new LogicException(
                    [
                        ''
                        . 'The `from[0]` should match regex: '
                        . $regexp . ' / ' . $attributeString,
                        //
                        $from,
                    ]
                )
            );
        }

        $symbols = [ '/', '|', '(', ')' ];

        $forbidden = implode('', $symbols);
        $forbidden = preg_quote($forbidden, '/');
        if (preg_match("/[{$forbidden}]/", $regexString)) {
            return Lib::refsError(
                $refs,
                new LogicException(
                    [
                        ''
                        . 'The `from[1]` should not contain symbols: '
                        . '[ ' . implode(' ][ ', $symbols) . ' ]',
                        $from,
                    ]
                )
            );
        }

        if (! Lib::type()->regex($var, $regexp = "/{$regexString}/")) {
            return Lib::refsError(
                $refs,
                new LogicException(
                    [ 'The `from[1]` caused invalid regex: ' . $regexp, $from ]
                )
            );
        }

        $regexString = "(?<{$attributeString}>{$regexString})";

        $instance = new static();
        $instance->pattern = $patternString;
        $instance->attribute = $attributeString;
        $instance->regex = $regexString;

        return Lib::refsResult($refs, $instance);
    }
}
