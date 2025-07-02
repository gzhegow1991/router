<?php

namespace Gzhegow\Router;


interface RouterAwareInterface
{
    /**
     * @param null|RouterInterface $router
     *
     * @return void
     */
    public function setRouter(?RouterInterface $router) : void;
}
