<?php

namespace Gzhegow\Router\Demo\Handler\Fallback;


class DemoThrowableFallback
{
    public function __invoke(
        \Throwable $e, $input,
        array $context = [],
        array $args = []
    )
    {
        echo __METHOD__ . "\n";

        // > any throwable is supported
        // if (! is_a($e, \Throwable::class)) {
        //     throw $e;
        // }

        return __METHOD__ . ' result.';
    }
}
