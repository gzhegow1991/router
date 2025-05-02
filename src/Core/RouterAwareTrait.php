<?php

namespace Gzhegow\Router\Core;

trait RouterAwareTrait
{
    /**
     * @var RouterInterface
     */
    protected $router;


    /**
     * @param null|RouterInterface $router
     *
     * @return void
     */
    public function setRouter(?RouterInterface $router) : void
    {
        $this->router = $router;
    }
}
