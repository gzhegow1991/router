<?php

namespace Gzhegow\Router\Demo\Handler\Middleware;


class Demo2ndMiddleware
{
    public function __invoke(
        $fnNext, $input,
        array $context = [],
        array $args = []
    )
    {
        $method = __METHOD__;

        echo "@before :: {$method}" . PHP_EOL;

        $result = $fnNext($input, $args);

        echo "@after :: {$method}" . PHP_EOL;

        return $result;
    }
}
