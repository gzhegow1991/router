<?php

namespace Gzhegow\Router\Core\Route\Struct;

use Gzhegow\Lib\Modules\Php\Result\Result;


class RouteNameTag
{
    /**
     * @var array{ 0: RouteName, 1: RouteTag }
     */
    protected $value;

    /**
     * @var RouteName
     */
    protected $routeName;
    /**
     * @var RouteTag
     */
    protected $routeTag;


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
        if (! is_array($from)) {
            return Result::err(
                $ret,
                [ 'The `from` should be array', $from ],
                [ __FILE__, __LINE__ ]
            );
        }

        $fromName = $from[ 'name' ] ?? $from[ 0 ];
        $fromTag = $from[ 'tag' ] ?? $from[ 1 ];

        $fromNameObject = null;
        if (null !== $fromName) {
            $fromNameObject = RouteName::from($fromName, $retCur = Result::asValue());
            if ($retCur->isErr()) {
                return Result::err($ret, $retCur);
            }
        }

        $fromTagObject = null;
        if (null !== $fromTag) {
            $fromTagObject = RouteTag::from($fromTag, $retCur = Result::asValue());
            if ($retCur->isErr()) {
                return Result::err($ret, $retCur);
            }
        }

        if (true
            && ! $fromNameObject
            && ! $fromTagObject
        ) {
            return Result::err(
                $ret,
                [ 'The `from` should contains at least `name` or `tag` keys', $from ],
                [ __FILE__, __LINE__ ]
            );
        }

        $instance = new static();

        $instance->value = [ $fromNameObject, $fromTagObject ];

        $instance->routeName = $fromNameObject;
        $instance->routeTag = $fromTagObject;

        return Result::ok($ret, $instance);
    }


    public function getValue() : array
    {
        return $this->value;
    }


    /**
     * @return array{ 0: string|null, 1: string|null }
     */
    public function getPair() : array
    {
        $this->hasNameString($nameString);
        $this->hasTagString($tagString);

        return [ $nameString, $tagString ];
    }


    public function hasRouteName(?RouteName &$refRouteName = null) : bool
    {
        $refRouteName = null;

        if (null !== $this->routeName) {
            $refRouteName = $this->routeName;

            return true;
        }

        return false;
    }

    public function getRouteName() : RouteName
    {
        return $this->routeName;
    }


    public function hasRouteTag(?RouteTag &$refRouteTag = null) : bool
    {
        $refRouteTag = null;

        if (null !== $this->routeTag) {
            $refRouteTag = $this->routeTag;

            return true;
        }

        return false;
    }

    public function getRouteTag() : RouteTag
    {
        return $this->routeTag;
    }


    public function hasNameString(?string &$refNameString = null) : bool
    {
        $refNameString = null;

        if (null !== $this->routeName) {
            $refNameString = $this->routeName->getValue();

            return true;
        }

        return false;
    }

    public function getNameString() : string
    {
        return $this->routeName->getValue();
    }


    public function hasTagString(?string &$refTagString = null) : bool
    {
        $refTagString = null;

        if (null !== $this->routeTag) {
            $refTagString = $this->routeTag->getValue();

            return true;
        }

        return false;
    }

    public function getTagString() : string
    {
        return $this->routeTag->getValue();
    }
}
