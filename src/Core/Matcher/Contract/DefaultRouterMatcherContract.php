<?php

namespace Gzhegow\Router\Core\Matcher\Contract;

use Gzhegow\Router\Core\Route\Route;
use Gzhegow\Lib\Modules\Php\Result\Result;
use Gzhegow\Router\Core\Route\Struct\RouteTag;
use Gzhegow\Router\Core\Route\Struct\RouteName;
use Gzhegow\Router\Core\Route\Struct\RouteMethod;


class DefaultRouterMatcherContract implements RouterMatcherContractInterface
{
    /**
     * @var string|false|null
     */
    protected $name;
    /**
     * @var string|false|null
     */
    protected $tag;

    /**
     * @var string|false|null
     */
    protected $method;
    /**
     * @var string|false|null
     */
    protected $path;


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

        $fromName = $from[ 'name' ] ?? $from[ 0 ] ?? null;
        $fromTag = $from[ 'tag' ] ?? $from[ 1 ] ?? null;
        $fromMethod = $from[ 'method' ] ?? $from[ 2 ] ?? null;
        $fromPath = $from[ 'path' ] ?? $from[ 3 ] ?? null;

        if (null === $fromName) {
            $fromName = null;

        } elseif (false === $fromName) {
            $fromName = false;

        } else {
            $fromName = RouteName::from($fromName, $retCur = Result::asValueNull());

            if ($retCur->isErr()) {
                return Result::err($ret, $retCur);
            }

            $fromName = $fromName->getValue();
        }

        if (null === $fromTag) {
            $fromTag = null;

        } elseif (false === $fromTag) {
            $fromTag = false;

        } else {
            $fromTag = RouteTag::from($fromTag, $retCur = Result::asValueNull());

            if ($retCur->isErr()) {
                return Result::err($ret, $retCur);
            }

            $fromTag = $fromTag->getValue();
        }

        if (null === $fromMethod) {
            $fromMethod = null;

        } elseif (false === $fromMethod) {
            $fromMethod = false;

        } else {
            $fromMethod = RouteMethod::from($fromMethod, $retCur = Result::asValueNull());

            if ($retCur->isErr()) {
                return Result::err($ret, $retCur);
            }

            $fromMethod = $fromMethod->getValue();
        }

        if (null === $fromPath) {
            $fromPath = null;

        } elseif (false === $fromPath) {
            $fromPath = false;

        } else {
            $fromPath = RouteMethod::from($fromPath, $retCur = Result::asValueNull());

            if ($retCur->isErr()) {
                return Result::err($ret, $retCur);
            }

            $fromPath = $fromPath->getValue();
        }

        $instance = new static();
        $instance->name = $fromName;
        $instance->tag = $fromTag;
        $instance->method = $fromMethod;
        $instance->path = $fromPath;

        return Result::ok($ret, $instance);
    }


    /**
     * @return array{
     *     0: string|false|null,
     *     1: string|false|null,
     *     2: string|false|null,
     * }
     */
    public function getValue() : array
    {
        return [ $this->name, $this->tag, $this->method ];
    }


    public function isMatch(Route $route) : bool
    {
        if (null !== $this->name) {
            if (null === $route->name) {
                if (false !== $this->name) {
                    return false;
                }

            } else {
                if ($route->name !== $this->name) {
                    return false;
                }
            }
        }

        if (null !== $this->tag) {
            if ([] === $route->tagIndex) {
                if (false !== $this->tag) {
                    return false;
                }

            } else {
                if (! isset($route->tagIndex[ $this->tag ])) {
                    return false;
                }
            }
        }

        if (null !== $this->method) {
            if ([] === $route->methodIndex) {
                if (false !== $this->method) {
                    return false;
                }

            } else {
                if (! isset($route->methodIndex[ $this->method ])) {
                    return false;
                }
            }
        }

        if (null !== $this->path) {
            if ($route->path !== $this->path) {
                return false;
            }
        }

        return true;
    }
}
