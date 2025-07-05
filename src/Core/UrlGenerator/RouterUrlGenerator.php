<?php

namespace Gzhegow\Router\Core\UrlGenerator;

use Gzhegow\Lib\Lib;
use Gzhegow\Router\RouterInterface;
use Gzhegow\Router\Core\Route\Route;
use Gzhegow\Router\Exception\LogicException;
use Gzhegow\Router\Exception\RuntimeException;
use Gzhegow\Router\Core\Matcher\RouterMatcherInterface;


class RouterUrlGenerator implements RouterUrlGeneratorInterface
{
    /**
     * @var RouterMatcherInterface
     */
    protected $routerMatcher;


    public function initialize(RouterInterface $router) : void
    {
        $this->routerMatcher = $router->getRouterMatcher();
    }


    /**
     * @param (string|Route)[] $routes
     *
     * @return string[]
     */
    public function urls(array $routes, array $attributes = []) : array
    {
        $theType = Lib::type();

        $result = [];

        $routeList = [];
        $routeNameList = [];
        foreach ( $routes as $idx => $route ) {
            $result[ $idx ] = null;

            if ($route instanceof Route) {
                $routeList[ $idx ] = $route;

            } elseif ($theType->string_not_empty($routeNameString, $route)) {
                $routeNameList[ $idx ] = $routeNameString;

            } else {
                throw new LogicException(
                    [
                        'Each of `routes` should be a string (route `name`) or instance of: ' . Route::class,
                        $routes,
                    ]
                );
            }
        }

        if ([] !== $routeNameList) {
            $batch = $this->routerMatcher->matchAllByNames($routeNameList);

            foreach ( $batch as $idx => $items ) {
                if ($items) {
                    $routeList[ $idx ] = reset($items);
                }
            }
        }

        if ([] !== $routeList) {
            foreach ( $routeList as $idx => $route ) {
                $attributesCurrent = $this->generateUrlAttributes($route, $attributes, $idx);

                $result[ $idx ] = $this->generateUrl($route, $attributesCurrent);
            }
        }

        return $result;
    }

    /**
     * @param Route|string $route
     */
    public function url($route, array $attributes = []) : string
    {
        [ $url ] = $this->urls([ $route ], $attributes);

        return $url;
    }


    protected function generateUrl(Route $route, array $attributes = []) : string
    {
        $attributesCurrent = $this->generateUrlAttributes($route, $attributes);

        $search = [];
        foreach ( $attributesCurrent as $key => $attr ) {
            $search[ '{' . $key . '}' ] = $attr;
        }

        $url = str_replace(
            array_keys($search),
            array_values($search),
            $route->path
        );

        return $url;
    }

    protected function generateUrlAttributes(Route $route, array $attributes = [], $idx = null) : array
    {
        $result = [];

        foreach ( $route->compiledActionAttributes as $key => $attr ) {
            $attr = null
                ?? (is_array($attributes[ $key ] ?? null) ? $attributes[ $key ][ $idx ] : null)
                ?? (is_array($attributes) ? $attributes[ $key ] : null)
                ?? $attr;

            if (null === $attr) {
                throw new RuntimeException(
                    [
                        'Missing attributes: '
                        . "attributes[ {$key} ][ {$idx} ], attributes[ {$key} ]",
                        $attributes,
                    ]
                );
            }

            $result[ $key ] = $attr;
        }

        return $result;
    }
}
