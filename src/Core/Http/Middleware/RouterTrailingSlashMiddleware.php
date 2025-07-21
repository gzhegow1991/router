<?php

namespace Gzhegow\Router\Core\Http\Middleware;

use Gzhegow\Lib\Lib;
use Gzhegow\Router\Core\Route\Route;


class RouterTrailingSlashMiddleware
{
    public function __invoke(
        $fnNext,
        $input,
        array $context = [],
        array $args = []
    )
    {
        $thePhp = Lib::php();

        if ($thePhp->is_terminal()) {
            return $fnNext($input, $args);
        }

        /**
         * @var Route $route
         */
        $route = $args[ 0 ] ?? null;

        if (null === $route) {
            return $fnNext($input, $args);
        }

        if (null === $route->requestContract) {
            return $fnNext($input, $args);
        }

        $contractRequestUri = $route->requestContract->getRequestUri();
        $dispatchRequestUri = $route->dispatchRequestUri;

        if ($dispatchRequestUri !== $contractRequestUri) {
            header('Location: ' . $dispatchRequestUri, true, 301);

            exit(0);
        }

        $result = $fnNext($input, $args);

        return $result;
    }
}
