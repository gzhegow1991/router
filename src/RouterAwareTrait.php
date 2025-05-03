<?php

namespace Gzhegow\Router;

use Gzhegow\Router\RouterInterface;


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
