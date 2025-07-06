<?php

namespace Gzhegow\Router\Demo\Handler\Controller;

use Gzhegow\Router\Core\Route\Route;


class DemoController
{
    public function indexGet()
    {
        echo __METHOD__ . "\n";

        /** @var Route $route */
        $route = $args[ 0 ] ?? null;

        if (null !== $route) {
            echo '[ LANG ] ' . $route->dispatchActionAttributes[ 'lang' ] . "\n";
        }

        return 1;
    }

    public function indexPost()
    {
        echo __METHOD__ . "\n";

        /** @var Route $route */
        $route = $args[ 0 ] ?? null;

        if (null !== $route) {
            echo '[ LANG ] ' . $route->dispatchActionAttributes[ 'lang' ] . "\n";
        }

        return 1;
    }


    public function helloWorldGet()
    {
        echo __METHOD__ . "\n";

        /** @var Route $route */
        $route = $args[ 0 ] ?? null;

        if (null !== $route) {
            echo '[ LANG ] ' . $route->dispatchActionAttributes[ 'lang' ] . "\n";
        }

        return 1;
    }

    public function helloWorldPost()
    {
        echo __METHOD__ . "\n";

        /** @var Route $route */
        $route = $args[ 0 ] ?? null;

        if (null !== $route) {
            echo '[ LANG ] ' . $route->dispatchActionAttributes[ 'lang' ] . "\n";
        }

        return 1;
    }


    public function apiV1UserMainGet()
    {
        echo __METHOD__ . "\n";

        return 1;
    }

    public function apiV1UserMainPost()
    {
        echo __METHOD__ . "\n";

        return 1;
    }


    public function errorLogic()
    {
        echo __METHOD__ . "\n";

        throw new \LogicException();
    }

    public function errorRuntime()
    {
        echo __METHOD__ . "\n";

        throw new \RuntimeException();
    }
}
