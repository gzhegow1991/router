<?php

namespace Gzhegow\Router\Core\Dispatcher;

use Gzhegow\Router\RouterInterface;
use Gzhegow\Router\Core\Route\Route;
use Gzhegow\Router\Exception\Exception\DispatchException;


interface RouterDispatcherInterface
{
    public function initialize(RouterInterface $router) : void;


    /**
     * @return mixed
     * @throws DispatchException
     */
    public function dispatch(
        RouterDispatcherContract $contract,
        $input = null,
        &$context = null,
        array $args = []
    );


    public function getDispatchContract() : RouterDispatcherContract;

    public function getDispatchRequestMethod() : string;

    public function getDispatchRequestUri() : string;


    public function getDispatchRoute() : Route;

    public function getDispatchActionAttributes() : array;
}
