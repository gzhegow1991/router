<?php

namespace Gzhegow\Router\Core\Route\Struct;

use Gzhegow\Lib\Lib;
use Gzhegow\Lib\Modules\Php\Result\Result;


class HttpPath
{
    /**
     * @var string
     */
    protected $value;

    /**
     * @var string
     */
    protected $path;

    /**
     * @var array
     */
    protected $query;
    /**
     * @var string
     */
    protected $queryString;

    /**
     * @var string
     */
    protected $fragment;


    private function __construct()
    {
    }


    public function __toString()
    {
        return $this->value;
    }


    /**
     * @return static|bool|null
     */
    public static function from($from, $ret = null)
    {
        $retCur = Result::asValue();

        $instance = null
            ?? static::fromStatic($from, $retCur)
            ?? static::fromString($from, $retCur);

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
    public static function fromString($from, $ret = null)
    {
        $theType = Lib::type();

        if (! $theType->string_not_empty($fromString, $from)) {
            return Result::err(
                $ret,
                [ 'The `from` should be non-empty string', $from ],
                [ __FILE__, __LINE__ ]
            );
        }

        if (0 !== strpos($fromString, '/')) {
            return Result::err(
                $ret,
                [ 'The `from` should start with `/` sign', $from ],
                [ __FILE__, __LINE__ ]
            );
        }

        $status = $theType->link(
            $fromLink,
            $fromString, null, null,
            null,
            [ &$parseUrl ]
        );

        if (! $status) {
            return Result::err(
                $ret,
                [ 'The `from` should be valid path', $from ],
                [ __FILE__, __LINE__ ]
            );
        }

        $fromPath = $parseUrl[ 'path' ];

        $fromIsQuery = null;
        $fromQuery = null;
        $fromQueryString = null;
        if (isset($parseUrl[ 'query' ])) {
            $fromIsQuery = '?';
            $fromQueryString = $parseUrl[ 'query' ];

            parse_str($parseUrl[ 'query' ], $fromQuery);
        }

        $isFragment = null;
        $fromFragment = null;
        if (isset($parseUrl[ 'fragment' ])) {
            $isFragment = '#';
            $fromFragment = $parseUrl[ 'fragment' ];
        }

        $fromUri = "{$fromPath}{$fromIsQuery}{$fromQueryString}{$isFragment}{$fromFragment}";

        $instance = new static();
        $instance->value = $fromUri;

        $instance->path = $fromPath;

        $instance->query = $fromQuery;
        $instance->queryString = $fromQueryString;

        $instance->fragment = $fromFragment;

        return Result::ok($ret, $instance);
    }


    public function getValue() : string
    {
        return $this->value;
    }


    public function getPath() : string
    {
        return $this->path;
    }


    public function hasQuery(?array &$refQuery = null) : bool
    {
        $refQuery = null;

        if (null !== $this->query) {
            $refQuery = $this->query;

            return true;
        }

        return $refQuery;
    }

    public function getQuery() : array
    {
        return $this->query;
    }


    public function hasQueryString(?string &$refQueryString = null) : bool
    {
        $refQueryString = null;

        if (null !== $this->queryString) {
            $refQueryString = $this->queryString;

            return true;
        }

        return $refQueryString;
    }

    public function getQueryString() : string
    {
        return $this->queryString;
    }


    public function hasFragment(?string &$refFragment = null) : bool
    {
        $refFragment = null;

        if (null !== $this->fragment) {
            $refFragment = $this->fragment;

            return true;
        }

        return $refFragment;
    }

    public function getFragment() : string
    {
        return $this->fragment;
    }
}
