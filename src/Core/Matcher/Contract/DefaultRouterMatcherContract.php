<?php

namespace Gzhegow\Router\Core\Matcher\Contract;

use Gzhegow\Lib\Modules\Type\Ret;
use Gzhegow\Router\Core\Route\Route;
use Gzhegow\Router\Core\Route\Struct\RouteTag;
use Gzhegow\Router\Core\Route\Struct\RouteName;
use Gzhegow\Router\Core\Route\Struct\RoutePath;
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
     * @return static|Ret<static>
     */
    public static function from($from, ?array $fallback = null)
    {
        $ret = Ret::new();

        $instance = null
            ?? static::fromStatic($from)->orNull($ret)
            ?? static::fromArray($from)->orNull($ret);

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
    public static function fromArray($from, ?array $fallback = null)
    {
        if (! is_array($from)) {
            return Ret::throw(
                $fallback,
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
            $ret = RouteName::from($fromName);

            if ($ret->isFail()) {
                return Ret::throw($fallback, $ret);
            }

            $routeName = $ret->getValue();

            $fromName = $routeName->getValue();
        }

        if (null === $fromTag) {
            $fromTag = null;

        } elseif (false === $fromTag) {
            $fromTag = false;

        } else {
            $ret = RouteTag::from($fromTag);

            if ($ret->isFail()) {
                return Ret::throw($fallback, $ret);
            }

            $routeTag = $ret->getValue();

            $fromTag = $routeTag->getValue();
        }

        if (null === $fromMethod) {
            $fromMethod = null;

        } elseif (false === $fromMethod) {
            $fromMethod = false;

        } else {
            $ret = RouteMethod::from($fromMethod);

            if ($ret->isFail()) {
                return Ret::throw($fallback, $ret);
            }

            $routeMethod = $ret->getValue();

            $fromMethod = $routeMethod->getValue();
        }

        if (null === $fromPath) {
            $fromPath = null;

        } elseif (false === $fromPath) {
            $fromPath = false;

        } else {
            $ret = RoutePath::from($fromPath);

            if ($ret->isFail()) {
                return Ret::throw($fallback, $ret);
            }

            $routePath = $ret->getValue();

            $fromPath = $routePath->getValue();
        }

        $instance = new static();
        $instance->name = $fromName;
        $instance->tag = $fromTag;
        $instance->method = $fromMethod;
        $instance->path = $fromPath;

        return Ret::ok($fallback, $instance);
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
