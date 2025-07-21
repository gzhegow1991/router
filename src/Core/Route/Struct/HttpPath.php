<?php

namespace Gzhegow\Router\Core\Route\Struct;

use Gzhegow\Lib\Lib;
use Gzhegow\Lib\Modules\Type\Ret;


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
     * @return static|Ret<static>
     */
    public static function from($from, ?array $fallback = null)
    {
        $ret = Ret::new();

        $instance = null
            ?? static::fromStatic($from)->orNull($ret)
            ?? static::fromString($from)->orNull($ret);

        if ($ret->isFail()) {
            return Ret::throw($fallback, $ret);
        }

        return Ret::ok($fallback, $instance);
    }

    /**
     * @return static|Ret<static>
     */
    public static function fromStatic($from, ?array $fallback = null)
    {
        if ($from instanceof static) {
            return Ret::ok($fallback, $from);
        }

        return Ret::throw(
            $fallback,
            [ 'The `from` should be instance of: ' . static::class, $from ],
            [ __FILE__, __LINE__ ]
        );
    }

    /**
     * @return static|Ret<static>
     */
    public static function fromString($from, ?array $fallback = null)
    {
        $theType = Lib::type();

        if (! $theType->string_not_empty($from)->isOk([ &$fromString, &$ret ])) {
            return Ret::throw($fallback, $ret);
        }

        if (0 !== strpos($fromString, '/')) {
            return Ret::throw(
                $ret,
                [ 'The `from` should start with `/` sign', $from ],
                [ __FILE__, __LINE__ ]
            );
        }

        $status = $theType->link(
            $fromString, null, null,
            null,
            [ &$parseUrl ]
        )->isOk([ &$fromLink, &$ret ]);

        if (! $status) {
            return Ret::throw($fallback, $ret);
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

        return Ret::ok($fallback, $instance);
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
