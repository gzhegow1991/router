<?php

namespace Gzhegow\Router;

use Gzhegow\Router\RouterInterface;


interface RouterAwareInterface
{
    /**
     * @param null|RouterInterface $router
     *
     * @return void
     */
    public function setRouter(?RouterInterface $router) : void;
}
