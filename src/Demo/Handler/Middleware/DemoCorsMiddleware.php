<?php

namespace Gzhegow\Router\Demo\Handler\Middleware;

use Gzhegow\Router\Core\Http\Middleware\RouterCorsMiddleware;


class DemoCorsMiddleware extends RouterCorsMiddleware
{
    public function __invoke(
        $fnNext, $input,
        array $context = [],
        array $args = []
    )
    {
        $method = __METHOD__;

        echo "@before :: {$method}" . "\n";

        $result = parent::__invoke(
            $fnNext, $input,
            $context,
            $args
        );

        echo "@after :: {$method}" . "\n";

        return $result;
    }
}
