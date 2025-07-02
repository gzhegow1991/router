<?php

namespace Gzhegow\Router\Demo\Handler\Fallback;


class DemoRuntimeExceptionFallback
{
    public function __invoke(
        \Throwable $e, $input,
        array $context = [],
        array $args = []
    )
    {
        echo __METHOD__ . PHP_EOL;

        if (! is_a($e, \RuntimeException::class)) {
            return $e;
        }

        return __METHOD__ . ' result.';
    }
}
