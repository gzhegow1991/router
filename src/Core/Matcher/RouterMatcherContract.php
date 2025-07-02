<?php

namespace Gzhegow\Router\Core\Matcher;

use Gzhegow\Lib\Lib;
use Gzhegow\Lib\Modules\Php\Result\Result;


class RouterMatcherContract
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
     * @return static|bool|null
     */
    public static function fromArray($array, $ret = null)
    {
        if (! is_array($array)) {
            return Result::err(
                $ret,
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

        return Result::ok($ret, $instance);
    }


    /**
     * @return array<int, bool>
     */
    public function getIdIndex() : array
    {
        return $this->idIndex;
    }

    /**
     * @return array<string, bool>
     */
    public function getNameIndex() : array
    {
        return $this->nameIndex;
    }

    /**
     * @return array<string, bool>
     */
    public function getTagIndex() : array
    {
        return $this->tagIndex;
    }


    /**
     * @return array<string, bool>
     */
    public function getPathIndex() : array
    {
        return $this->pathIndex;
    }

    /**
     * @return array<string, bool>
     */
    public function getHttpMethodIndex() : array
    {
        return $this->httpMethodIndex;
    }
}
