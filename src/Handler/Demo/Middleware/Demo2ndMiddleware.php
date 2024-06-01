<?php

namespace Gzhegow\Router\Handler\Demo\Middleware;

class Demo2ndMiddleware
{
    public function __invoke(callable $fnNext, $input = null, $context = null) // : mixed
    {
        $method = __METHOD__;

        echo "@before :: {$method}" . PHP_EOL;

        $result = call_user_func(
            $fnNext,
            $input, $context
        );

        echo "@after :: {$method}" . PHP_EOL;

        return $result;
    }
}
