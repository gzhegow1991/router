<?php

namespace Gzhegow\Router\Contract;

use Gzhegow\Router\Lib;
use Gzhegow\Router\Exception\LogicException;


class RouterMatchContract
{
    /**
     * @var array<int, bool>
     */
    public $idIndex = [];
    /**
     * @var array<string, bool>
     */
    public $nameIndex = [];
    /**
     * @var array<string, bool>
     */
    public $tagIndex = [];

    /**
     * @var array<string, bool>
     */
    public $pathIndex = [];
    /**
     * @var array<string, bool>
     */
    public $httpMethodIndex = [];


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
     * @return static
     */
    protected static function fromStatic($static) : ?object
    {
        if (! is_a($static, static::class)) {
            return Lib::php_trigger_error([ 'The `from` should be instance of: ' . static::class, $static ]);
        }

        return $static;
    }

    /**
     * @return static
     */
    protected static function fromArray($array) : ?object
    {
        if (! is_array($array)) {
            return Lib::php_trigger_error([ 'The `from` should be array', $array ]);
        }

        $ids = [];
        $pathes = [];
        $methods = [];
        $names = [];
        $groups = [];

        if (isset($array[ 'id' ])) $ids[] = (array) $array[ 'id' ];
        if (isset($array[ 'ids' ])) $ids[] = (array) $array[ 'ids' ];

        if (isset($array[ 'path' ])) $pathes[] = (array) $array[ 'path' ];
        if (isset($array[ 'pathes' ])) $pathes[] = (array) $array[ 'pathes' ];

        if (isset($array[ 'httpMethod' ])) $methods[] = (array) $array[ 'httpMethod' ];
        if (isset($array[ 'httpMethods' ])) $methods[] = (array) $array[ 'httpMethods' ];

        if (isset($array[ 'name' ])) $names[] = (array) $array[ 'name' ];
        if (isset($array[ 'names' ])) $names[] = (array) $array[ 'names' ];

        if (isset($array[ 'tag' ])) $groups[] = (array) $array[ 'tag' ];
        if (isset($array[ 'tags' ])) $groups[] = (array) $array[ 'tags' ];

        $instance = new static();

        $instance->idIndex = Lib::array_int_index([], ...$ids);
        $instance->nameIndex = Lib::array_string_index([], ...$names);
        $instance->tagIndex = Lib::array_string_index([], ...$groups);

        $instance->pathIndex = Lib::array_string_index([], ...$pathes);
        $instance->httpMethodIndex = Lib::array_string_index([], ...$methods);

        return $instance;
    }
}
