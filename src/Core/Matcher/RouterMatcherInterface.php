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
     * @param int[] $idList
     *
     * @return Route[]
     */
    public function matchAllByIds(array $idList) : array;

    /**
     * @param int[] $idList
     */
    public function matchFirstByIds(array $idList) : ?Route;


    /**
     * @param (string|RouteName)[] $nameList
     *
     * @return Route[]|Route[][]
     */
    public function matchAllByNames(array $nameList, ?bool $unique = null) : array;

    /**
     * @param (string|RouteName)[] $nameList
     */
    public function matchFirstByNames(array $nameList) : ?Route;


    /**
     * @param (string|RouteTag)[] $tagList
     *
     * @return Route[]|Route[][]
     */
    public function matchAllByTags(array $tagList, ?bool $unique = null) : array;

    /**
     * @param (string|RouteTag)[] $tagList
     */
    public function matchFirstByTags(array $tagList) : ?Route;


    /**
     * @param array{
     *     name: string|false|null,
     *     tag: string|false|null,
     *     method: string|false|null,
     *     path: string|false|null,
     *     0: string|false|null,
     *     1: string|false|null,
     *     2: string|false|null,
     *     3: string|false|null,
     * }[] $paramsList
     *
     * @return Route[]|Route[][]
     */
    public function matchAllByParams(array $paramsList, ?bool $unique = null) : array;

    /**
     * @param array{
     *     name: string|false|null,
     *     tag: string|false|null,
     *     method: string|false|null,
     *     path: string|false|null,
     *     0: string|false|null,
     *     1: string|false|null,
     *     2: string|false|null,
     *     3: string|false|null,
     * }[] $paramsList
     */
    public function matchFirstByParams(array $paramsList) : ?Route;


    /**
     * @return Route[]
     */
    public function matchByContract(RouterMatcherContractInterface $contract) : array;

    public function matchFirstByContract(RouterMatcherContractInterface $contract) : ?Route;
}
