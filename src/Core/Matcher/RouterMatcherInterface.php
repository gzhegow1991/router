<?php

namespace Gzhegow\Router\Core\Matcher;

use Gzhegow\Router\RouterInterface;
use Gzhegow\Router\Core\Route\Route;
use Gzhegow\Router\Core\Route\Struct\RouteTag;
use Gzhegow\Router\Core\Route\Struct\RouteName;
use Gzhegow\Router\Core\Matcher\Contract\RouterMatcherContractInterface;


interface RouterMatcherInterface
{
    public function initialize(RouterInterface $router) : void;


    /**
     * @param int[] $routeIds
     *
     * @return Route[]
     */
    public function matchAllByIds(array $routeIds) : array;

    /**
     * @param int[] $routeIds
     */
    public function matchFirstByIds(array $routeIds) : ?Route;


    /**
     * @param (string|RouteName)[] $routeNames
     *
     * @return Route[]|Route[][]
     */
    public function matchAllByNames(array $routeNames, ?bool $unique = null) : array;

    /**
     * @param (string|RouteName)[] $routeNames
     */
    public function matchFirstByNames(array $routeNames) : ?Route;


    /**
     * @param (string|RouteTag)[] $routeTags
     *
     * @return Route[]|Route[][]
     */
    public function matchAllByTags(array $routeTags, ?bool $unique = null) : array;

    /**
     * @param (string|RouteTag)[] $routeTags
     */
    public function matchFirstByTags(array $routeTags) : ?Route;


    /**
     * @param array{
     *     0: string|false|null,
     *     1: string|false|null,
     *     2: string|false|null,
     * }[] $routeNameTagMethods
     *
     * @return Route[]|Route[][]
     */
    public function matchAllByNameTagMethods(array $routeNameTagMethods, ?bool $unique = null) : array;

    /**
     * @param array{
     *     0: string|false|null,
     *     1: string|false|null,
     *     2: string|false|null,
     * }[] $routeNameTagMethods
     */
    public function matchFirstByNameTagMethods(array $routeNameTagMethods) : ?Route;


    /**
     * @return Route[]
     */
    public function matchByContract(RouterMatcherContractInterface $contract) : array;

    public function matchFirstByContract(RouterMatcherContractInterface $contract) : ?Route;
}
