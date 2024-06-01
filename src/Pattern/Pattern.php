<?php

namespace Gzhegow\Router\Pattern;

use Gzhegow\Router\Lib;
use Gzhegow\Router\Router;
use Gzhegow\Router\Exception\LogicException;


class Pattern implements \Serializable, \JsonSerializable
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
    public static function from($from) : object
    {
        if (null === ($instance = static::tryFrom($from))) {
            throw new LogicException([
                'Unknown `from`: ' . Lib::php_dump($from),
            ]);
        }

        return $instance;
    }

    /**
     * @return static|null
     */
    public static function tryFrom($from) : ?object
    {
        $instance = null
            ?? static::fromStatic($from)
            ?? static::fromArray($from);

        return $instance;
    }

    /**
     * @return static|null
     */
    protected static function fromStatic($static) : ?object
    {
        if (! is_a($static, static::class)) {
            return Lib::php_trigger_error([ 'The `from` should be instance of: ' . static::class, $static ]);
        }

        return $static;
    }

    /**
     * @return static|null
     */
    protected static function fromArray($array) : ?object
    {
        if (! is_array($array)) {
            return Lib::php_trigger_error([
                'The `from` should be array',
                $array,
            ]);
        }

        [ $pattern, $regex ] = $array + [ null, null ];

        if (null === ($_pattern = Lib::filter_string($pattern))) {
            return Lib::php_trigger_error([
                'The `from[0]` should be non-empty string',
                $array,
            ]);
        }

        if (null === ($_regex = Lib::filter_string($regex))) {
            return Lib::php_trigger_error([
                'The `from[1]` should be non-empty string',
                $array,
            ]);
        }

        if (! (true
            && (Router::PATTERN_ENCLOSURE[ 0 ] === $_pattern[ 0 ])
            && (Router::PATTERN_ENCLOSURE[ 1 ] === $_pattern[ strlen($_pattern) - 1 ])
        )) {
            return Lib::php_trigger_error([
                'The `from[0]` should be wrapped with signs: '
                . '`' . Router::PATTERN_ENCLOSURE[ 0 ] . '`'
                . ' `' . Router::PATTERN_ENCLOSURE[ 1 ] . '`',
                $array,
            ]);
        }

        $_attribute = substr($_pattern, 1, -1);

        if (! preg_match($var = '/[a-z][a-z0-9_]*/', $_attribute)) {
            return Lib::php_trigger_error([
                'The `from[0]` should match regex: ' . $var . ' / ' . $_attribute,
                $array,
            ]);
        }

        $symbols = [ '/', '|', '(', ')' ];

        $forbidden = implode('', $symbols);
        $forbidden = preg_quote($forbidden, '/');
        if (preg_match("/[{$forbidden}]/", $_regex)) {
            $vars = [];
            foreach ( $symbols as $i => $symbol ) {
                $vars[ $i ] = "`{$symbol}`";
            }

            return Lib::php_trigger_error([
                'The `from[1]` should not contain symbols: ' . implode(',', $vars),
                $array,
            ]);
        }

        if (null === Lib::filter_regex($var = "/{$_regex}/")) {
            return Lib::php_trigger_error([
                'The `from[1]` caused invalid regex: ' . $var,
                $array,
            ]);
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

    public function jsonSerialize()
    {
        return $this->__serialize();
    }
}
