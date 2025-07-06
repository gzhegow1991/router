<?php

namespace Gzhegow\Router\Core\Dispatcher;

use Gzhegow\Router\RouterInterface;
use Gzhegow\Router\Core\Route\Route;
use Gzhegow\Lib\Modules\Func\Pipe\PipeContext;
use Gzhegow\Router\Exception\Exception\DispatchException;
use Gzhegow\Router\Core\Handler\Fallback\RouterGenericHandlerFallback;
use Gzhegow\Router\Core\Handler\Middleware\RouterGenericHandlerMiddleware;
use Gzhegow\Router\Core\Dispatcher\Contract\RouterDispatcherRouteContractInterface;
use Gzhegow\Router\Core\Dispatcher\Contract\RouterDispatcherRequestContractInterface;


interface RouterDispatcherInterface
{
    public function initialize(RouterInterface $router) : void;


    /**
     * @param mixed|RouterDispatcherRequestContractInterface|RouterDispatcherRouteContractInterface $contract
     * @param array{ 0: array }|PipeContext                                                         $context
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

    /**
     * @param array{ 0: array }|PipeContext $context
     *
     * @return mixed
     * @throws DispatchException
     */
    public function dispatchByRequest(
        RouterDispatcherRequestContractInterface $contract,
        $input = null,
        $context = null,
        array $args = []
    );

    /**
     * @param array{ 0: array }|PipeContext $context
     *
     * @return mixed
     * @throws DispatchException
     */
    public function dispatchByRoute(
        RouterDispatcherRouteContractInterface $contract,
        $input = null,
        $context = null,
        array $args = []
    );


    public function hasRequestContract(?RouterDispatcherRequestContractInterface &$contract = null) : bool;

    public function getRequestContract() : RouterDispatcherRequestContractInterface;


    public function hasRouteContract(?RouterDispatcherRouteContractInterface &$contract = null) : bool;

    public function getRouteContract() : RouterDispatcherRouteContractInterface;


    public function getDispatchRequestMethod() : string;

    public function getDispatchRequestPath() : string;

    public function getDispatchRequestUri() : string;


    public function getDispatchRoute() : Route;

    public function getDispatchActionAttributes() : array;


    /**
     * @return RouterGenericHandlerMiddleware[]
     */
    public function getDispatchMiddlewareIndex() : array;

    /**
     * @return RouterGenericHandlerFallback[]
     */
    public function getDispatchFallbackIndex() : array;
}
