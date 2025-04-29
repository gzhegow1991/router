<?php

namespace Gzhegow\Router\Core\Contract;

use Gzhegow\Lib\Lib;
use Gzhegow\Lib\Modules\Php\Result\Result;


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
     * @return static|bool|null
     */
    public static function from($from, $ctx = null)
    {
        Result::parse($cur);

        $instance = null
            ?? static::fromStatic($from, $cur)
            ?? static::fromArray($from, $cur);

        if ($cur->isErr()) {
            return Result::err($ctx, $cur);
        }

        return Result::ok($ctx, $instance);
    }

    /**
     * @return static|bool|null
     */
    public static function fromStatic($from, $ctx = null)
    {
        if ($from instanceof static) {
            return Result::ok($ctx, $from);
        }

        return Result::err(
            $ctx,
            [ 'The `from` should be instance of: ' . static::class, $from ],
            [ __FILE__, __LINE__ ]
        );
    }

    /**
     * @return static|bool|null
     */
    public static function fromArray($array, $ctx = null)
    {
        if (! is_array($array)) {
            return Result::err(
                $ctx,
                [ 'The `from` should be array', $array ],
                [ __FILE__, __LINE__ ]
            );
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

        $instance->idIndex = Lib::arr()->index_int([], ...$ids);
        $instance->nameIndex = Lib::arr()->index_string([], ...$names);
        $instance->tagIndex = Lib::arr()->index_string([], ...$groups);

        $instance->pathIndex = Lib::arr()->index_string([], ...$pathes);
        $instance->httpMethodIndex = Lib::arr()->index_string([], ...$methods);

        return Result::ok($ctx, $instance);
    }
}
