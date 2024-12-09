<?php

namespace Gzhegow\Router\Pattern;

use Gzhegow\Lib\Lib;
use Gzhegow\Router\Router;
use Gzhegow\Router\Exception\LogicException;


class Pattern implements \Serializable
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


    /**
     * @return static
     */
    public static function from($from) : self
    {
        $instance = static::tryFrom($from, $error);

        if (null === $instance) {
            throw $error;
        }

        return $instance;
    }

    /**
     * @return static|null
     */
    public static function tryFrom($from, \Throwable &$last = null) : ?self
    {
        $last = null;

        Lib::php_errors_start($b);

        $instance = null
            ?? static::tryFromInstance($from)
            ?? static::tryFromArray($from);

        $errors = Lib::php_errors_end($b);

        if (null === $instance) {
            foreach ( $errors as $error ) {
                $last = new LogicException($error, null, $last);
            }
        }

        return $instance;
    }


    /**
     * @return static|null
     */
    public static function tryFromInstance($instance) : ?self
    {
        if (! is_a($instance, static::class)) {
            return Lib::php_error(
                [ 'The `from` should be instance of: ' . static::class, $instance ]
            );
        }

        return $instance;
    }

    /**
     * @return static|null
     */
    public static function tryFromArray($array) : ?self
    {
        if (! is_array($array)) {
            return Lib::php_error(
                [
                    'The `from` should be array',
                    $array,
                ]
            );
        }

        [ $pattern, $regex ] = $array + [ null, null ];

        if (null === ($_pattern = Lib::parse_string_not_empty($pattern))) {
            return Lib::php_error(
                [
                    'The `from[0]` should be non-empty string',
                    $array,
                ]
            );
        }

        if (null === ($_regex = Lib::parse_string_not_empty($regex))) {
            return Lib::php_error(
                [
                    'The `from[1]` should be non-empty string',
                    $array,
                ]
            );
        }

        if (! (true
            && (Router::PATTERN_ENCLOSURE[ 0 ] === $_pattern[ 0 ])
            && (Router::PATTERN_ENCLOSURE[ 1 ] === $_pattern[ strlen($_pattern) - 1 ])
        )) {
            return Lib::php_error(
                [
                    'The `from[0]` should be wrapped with signs: '
                    . '`' . Router::PATTERN_ENCLOSURE[ 0 ] . '`'
                    . ' `' . Router::PATTERN_ENCLOSURE[ 1 ] . '`',
                    $array,
                ]
            );
        }

        $_attribute = substr($_pattern, 1, -1);

        if (! preg_match($var = '/[a-z][a-z0-9_]*/', $_attribute)) {
            return Lib::php_error(
                [
                    'The `from[0]` should match regex: ' . $var . ' / ' . $_attribute,
                    $array,
                ]
            );
        }

        $symbols = [ '/', '|', '(', ')' ];

        $forbidden = implode('', $symbols);
        $forbidden = preg_quote($forbidden, '/');
        if (preg_match("/[{$forbidden}]/", $_regex)) {
            $vars = [];
            foreach ( $symbols as $i => $symbol ) {
                $vars[ $i ] = "`{$symbol}`";
            }

            return Lib::php_error(
                [
                    'The `from[1]` should not contain symbols: ' . implode(',', $vars),
                    $array,
                ]
            );
        }

        if (null === Lib::parse_regex($var = "/{$_regex}/")) {
            return Lib::php_error(
                [
                    'The `from[1]` caused invalid regex: ' . $var,
                    $array,
                ]
            );
        }

        $_regex = "(?<{$_attribute}>{$_regex})";

        $instance = new static();
        $instance->pattern = $_pattern;
        $instance->attribute = $_attribute;
        $instance->regex = $_regex;

        return $instance;
    }


    public function __serialize() : array
    {
        $vars = get_object_vars($this);

        return array_filter($vars);
    }

    public function __unserialize(array $data) : void
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
}
