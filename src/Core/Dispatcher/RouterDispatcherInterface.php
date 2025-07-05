<?php

namespace Gzhegow\Router\Core\Dispatcher;

use Gzhegow\Router\RouterInterface;
use Gzhegow\Router\Core\Route\Route;
use Gzhegow\Lib\Modules\Func\Pipe\PipeContext;
use Gzhegow\Router\Exception\Exception\DispatchException;


interface RouterDispatcherInterface
{
    public function initialize(RouterInterface $router) : void;


    /**
     * @param mixed|RouterDispatcherContract $contract
     * @param array{ 0: array }|PipeContext  $context
     *
     * @return mixed
     * @throws DispatchException
     */
    public function dispatch(
        $contract,
        $input = null,
        $context = null,
        array $args = []
    );


    public function getDispatchContract() : RouterDispatcherContract;

    public function getDispatchRequestMethod() : string;

    public function getDispatchRequestPath() : string;

    public function getDispatchRequestUri() : string;


    public function getDispatchRoute() : Route;

    public function getDispatchActionAttributes() : array;
}
