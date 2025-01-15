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


    /**
     * @return static
     */
    public static function from($from) // : static
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
    public static function tryFrom($from, \Throwable &$last = null) // : ?static
    {
        $last = null;

        Lib::php()->errors_start($b);

        $instance = null
            ?? static::tryFromInstance($from)
            ?? static::tryFromArray($from);

        $errors = Lib::php()->errors_end($b);

        if (null === $instance) {
            foreach ( $errors as $error ) {
                $last = new LogicException($error, $last);
            }
        }

        return $instance;
    }


    /**
     * @return static|null
     */
    public static function tryFromInstance($instance) // : ?static
    {
        if (! is_a($instance, static::class)) {
            return Lib::php()->error(
                [ 'The `from` should be instance of: ' . static::class, $instance ]
            );
        }

        return $instance;
    }

    /**
     * @return static|null
     */
    public static function tryFromArray($array) // : ?static
    {
        if (! is_array($array)) {
            return Lib::php()->error(
                [ 'The `from` should be array', $array ]
            );
        }

        [ $pattern, $regex ] = $array + [ null, null ];

        if (null === ($_pattern = Lib::parse()->string_not_empty($pattern))) {
            return Lib::php()->error(
                [ 'The `from[0]` should be non-empty string', $array ]
            );
        }

        if (null === ($_regex = Lib::parse()->string_not_empty($regex))) {
            return Lib::php()->error(
                [ 'The `from[1]` should be non-empty string', $array ]
            );
        }

        if (! (true
            && (Router::PATTERN_ENCLOSURE[ 0 ] === $_pattern[ 0 ])
            && (Router::PATTERN_ENCLOSURE[ 1 ] === $_pattern[ strlen($_pattern) - 1 ])
        )) {
            return Lib::php()->error(
                [
                    ''
                    . 'The `from[0]` should be wrapped with signs: '
                    . '`' . Router::PATTERN_ENCLOSURE[ 0 ] . '`'
                    . ' `' . Router::PATTERN_ENCLOSURE[ 1 ] . '`',
                    $array,
                ]
            );
        }

        $_attribute = substr($_pattern, 1, -1);

        if (! preg_match($var = '/[a-z][a-z0-9_]*/', $_attribute)) {
            return Lib::php()->error(
                [ 'The `from[0]` should match regex: ' . $var . ' / ' . $_attribute, $array ]
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

            return Lib::php()->error(
                [
                    ''
                    . 'The `from[1]` should not contain symbols: '
                    . implode(',', $vars),
                    $array,
                ]
            );
        }

        if (null === Lib::parse()->regex($var = "/{$_regex}/")) {
            return Lib::php()->error(
                [ 'The `from[1]` caused invalid regex: ' . $var, $array ]
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
