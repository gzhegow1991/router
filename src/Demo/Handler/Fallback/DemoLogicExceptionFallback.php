<?php

namespace Gzhegow\Router\Demo\Handler\Fallback;


class DemoLogicExceptionFallback
{
    /**
     * @throws \Throwable
     */
    public function __invoke(
        \Throwable $e, $input,
        array $context = [],
        array $args = []
    )
    {
        echo __METHOD__ . "\n";

        if (! is_a($e, \LogicException::class)) {
            throw $e;
        }

        return __METHOD__ . ' result.';
    }
}
