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
    public static function fromArray($from, $ret = null)
    {
        $thePhp = Lib::php();
        $theParse = Lib::parse();

        if (! is_array($from)) {
            return Result::err(
                $ret,
                [ 'The `from` should be array', $from ],
                [ __FILE__, __LINE__ ]
            );
        }

        $idList = [];
        $pathList = [];
        $httpMethodList = [];
        $nameList = [];
        $tagList = [];

        if (! empty($from[ 'id' ])) $idList = $thePhp->to_list($from[ 'id' ]);
        if (! empty($from[ 'path' ])) $pathList = $thePhp->to_list($from[ 'path' ]);
        if (! empty($from[ 'httpMethod' ])) $httpMethodList = $thePhp->to_list($from[ 'httpMethod' ]);
        if (! empty($from[ 'name' ])) $nameList = $thePhp->to_list($from[ 'name' ]);
        if (! empty($from[ 'tag' ])) $tagList = $thePhp->to_list($from[ 'tag' ]);

        $idIndex = [];
        $pathIndex = [];
        $httpMethodIndex = [];
        $nameIndex = [];
        $tagIndex = [];

        foreach ( $idList as $i => $id ) {
            if (null === ($idInt = $theParse->int_positive($id))) {
                return Result::err(
                    $ret,
                    [ 'Each of `from[id]` should be positive integer', $id, $i, $from ],
                    [ __FILE__, __LINE__ ],
                );
            }

            $idIndex[ $idInt ] = true;
        }
        foreach ( $pathList as $i => $path ) {
            if (null === ($pathString = $theParse->string_not_empty($path))) {
                return Result::err(
                    $ret,
                    [ 'Each of `from[path]` should be positive integer', $path, $i, $from ],
                    [ __FILE__, __LINE__ ],
                );
            }

            $pathIndex[ $pathString ] = true;
        }
        foreach ( $httpMethodList as $i => $httpMethod ) {
            if (null === ($httpMethodString = $theParse->string_not_empty($httpMethod))) {
                return Result::err(
                    $ret,
                    [ 'Each of `from[httpMethod]` should be positive integer', $httpMethod, $i, $from ],
                    [ __FILE__, __LINE__ ],
                );
            }

            $httpMethodIndex[ $httpMethodString ] = true;
        }
        foreach ( $nameList as $i => $name ) {
            if (null === ($nameString = $theParse->string_not_empty($name))) {
                return Result::err(
                    $ret,
                    [ 'Each of `from[name]` should be positive integer', $name, $i, $from ],
                    [ __FILE__, __LINE__ ],
                );
            }

            $nameIndex[ $nameString ] = true;
        }
        foreach ( $tagList as $i => $tag ) {
            if (null === ($tagString = $theParse->string_not_empty($tag))) {
                return Result::err(
                    $ret,
                    [ 'Each of `from[tag]` should be positive integer', $tag, $i, $from ],
                    [ __FILE__, __LINE__ ],
                );
            }

            $tagIndex[ $tagString ] = true;
        }

        $instance = new static();

        $instance->idIndex = $idIndex;
        $instance->pathIndex = $pathIndex;
        $instance->httpMethodIndex = $httpMethodIndex;
        $instance->nameIndex = $nameIndex;
        $instance->tagIndex = $tagIndex;

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
