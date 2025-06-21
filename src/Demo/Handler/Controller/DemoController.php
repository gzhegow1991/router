<?php

namespace Gzhegow\Router\Demo\Handler\Controller;

class DemoController
{
    public function indexGet()
    {
        echo __METHOD__ . PHP_EOL;

        return 1;
    }

    public function indexPost()
    {
        echo __METHOD__ . PHP_EOL;

        return 1;
    }


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


    public function errorLogic()
    {
        echo __METHOD__ . PHP_EOL;

        throw new \LogicException();
    }

    public function errorRuntime()
    {
        echo __METHOD__ . PHP_EOL;

        throw new \RuntimeException();
    }
}
