<?php

namespace Gzhegow\Router\Core\Handler\Demo\Middleware;

use Gzhegow\Router\Core\Handler\Middleware\CorsMiddleware;


class DemoCorsMiddleware extends CorsMiddleware
{
    public function __invoke($process, $input = null, $context = null, $state = null)
    {
        $method = __METHOD__;

        echo "@before :: {$method}" . PHP_EOL;

        $result = parent::__invoke($process, $input, $context, $state);

        echo "@after :: {$method}" . PHP_EOL;

        return $result;
    }
}
