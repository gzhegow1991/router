<?php

namespace Gzhegow\Router\Demo\Handler\Middleware;

use Gzhegow\Pipeline\Process\PipelineProcessInterface;


class Demo2ndMiddleware
{
    public function __invoke(PipelineProcessInterface $pipeline, $input = null, $context = null)
    {
        $method = __METHOD__;

        echo "@before :: {$method}" . PHP_EOL;

        $result = $pipeline->next($input, $context);

        echo "@after :: {$method}" . PHP_EOL;

        return $result;
    }
}
