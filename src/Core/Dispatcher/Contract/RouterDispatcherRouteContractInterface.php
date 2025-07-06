<?php

namespace Gzhegow\Router\Core\Dispatcher\Contract;

use Gzhegow\Router\Core\Route\Route;


interface RouterDispatcherRouteContractInterface
{
    /**
     * @return Route
     */
    public function getRoute() : Route;

    /**
     * @return array
     */
    public function getRouteAttributes() : array;
}
