<?php

namespace Gzhegow\Router\Core\Http\Middleware;

use Gzhegow\Router\Core\Route\Route;


class TrailingSlashMiddleware
{
    public function __invoke(
        $fnNext, $input,
        array $context = [],
        array $args = []
    )
    {
        /** @var Route $route */

        $route = $args[ 0 ] ?? null;

        if (null !== $route) {
            $dispatchRequestUri = $route->dispatchRequestUri;
            $contractRequestUri = $route->dispatchContract->getRequestUri();

            if ($dispatchRequestUri !== $contractRequestUri) {
                header('Location: ' . $dispatchRequestUri, true, 301);

                exit(0);
            }
        }

        $result = $fnNext($input, $args);

        return $result;
    }
}
