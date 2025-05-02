<?php

namespace Gzhegow\Router\Core;


interface RouterAwareInterface
{
    /**
     * @param null|RouterInterface $router
     *
     * @return void
     */
    public function setRouter(?RouterInterface $router) : void;
}
