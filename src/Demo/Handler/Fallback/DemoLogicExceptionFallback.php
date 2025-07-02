<?php

namespace Gzhegow\Router\Demo\Handler\Fallback;


class DemoLogicExceptionFallback
{
    public function __invoke(
        \Throwable $e, $input,
        array $context = [],
        array $args = []
    )
    {
        echo __METHOD__ . PHP_EOL;

        if (! is_a($e, \LogicException::class)) {
            return $e;
        }

        return __METHOD__ . ' result.';
    }
}
