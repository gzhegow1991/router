<?php

namespace Gzhegow\Router\Demo\Handler\Middleware;

use Gzhegow\Router\Core\Handler\Middleware\CorsMiddleware;


class DemoCorsMiddleware extends CorsMiddleware
{
    public function __invoke(
        $fnNext, $input,
        array $context = [],
        array $args = []
    )
    {
        $method = __METHOD__;

        echo "@before :: {$method}" . PHP_EOL;

        $result = parent::__invoke(
            $fnNext, $input,
            $context,
            $args
        );

        echo "@after :: {$method}" . PHP_EOL;

        return $result;
    }
}
