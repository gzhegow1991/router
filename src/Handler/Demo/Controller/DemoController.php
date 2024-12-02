<?php

namespace Gzhegow\Router\Handler\Demo\Controller;

class DemoController
{
    public function mainGet()
    {
        echo __METHOD__ . PHP_EOL;

        return 1;
    }

    public function mainPost()
    {
        echo __METHOD__ . PHP_EOL;

        return 1;
    }


    public function logic()
    {
        echo __METHOD__ . PHP_EOL;

        throw new \LogicException();
    }

    public function runtime()
    {
        echo __METHOD__ . PHP_EOL;

        throw new \RuntimeException();
    }
}
