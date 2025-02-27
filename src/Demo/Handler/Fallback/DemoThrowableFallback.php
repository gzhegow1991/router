<?php

namespace Gzhegow\Router\Demo\Handler\Fallback;


class DemoThrowableFallback
{
    public function __invoke(\Throwable $e, $input = null, $context = null)
    {
        echo __METHOD__ . PHP_EOL;

        // if (! is_a($e, \Throwable::class)) return null;

        return __METHOD__ . ' result.';
    }
}
