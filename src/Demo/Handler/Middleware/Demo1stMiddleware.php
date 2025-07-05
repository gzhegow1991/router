<?php

namespace Gzhegow\Router\Demo\Handler\Middleware;


class Demo1stMiddleware
{
    public function __invoke(
        $fnNext, $input,
        array $context = [],
        array $args = []
    )
    {
        $method = __METHOD__;

        echo "@before :: {$method}" . "\n";

        $result = $fnNext($input, $args);

        echo "@after :: {$method}" . "\n";

        return $result;
    }
}
