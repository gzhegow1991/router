<?php

namespace Gzhegow\Router\Core\UrlGenerator;

use Gzhegow\Router\RouterInterface;
use Gzhegow\Router\Core\Route\Route;


interface RouterUrlGeneratorInterface
{
    public function initialize(RouterInterface $router) : void;


    /**
     * @param (string|Route)[] $routes
     *
     * @return string[]
     */
    public function urls(array $routes, array $attributes = []) : array;

    /**
     * @param Route|string $route
     */
    public function url($route, array $attributes = []) : string;
}
