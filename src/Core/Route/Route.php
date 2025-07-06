<?php

namespace Gzhegow\Router\Core\Route;

use Gzhegow\Router\Core\Handler\Action\RouterGenericHandlerAction;
use Gzhegow\Router\Core\Dispatcher\Contract\RouterDispatcherRequestContract;
use Gzhegow\Router\Core\Handler\Fallback\RouterGenericHandlerFallback;
use Gzhegow\Router\Core\Handler\Middleware\RouterGenericHandlerMiddleware;


class Route implements \Serializable
{
    /**
     * @var int
     */
    public $id;

    /**
     * @var string
     */
    public $path;
    /**
     * @var string
     */
    public $compiledPathRegex;

    /**
     * @var RouterGenericHandlerAction
     */
    public $action;
    /**
     * @var array<string, mixed>
     */
    public $compiledActionAttributes = [];

    /**
     * @var string|null
     */
    public $name;

    /**
     * @var array<string, bool>
     */
    public $methodIndex = [];
    /**
     * @var array<string, bool>
     */
    public $tagIndex = [];

    /**
     * @var RouterDispatcherRequestContract
     */
    public $requestContract;

    /**
     * @var string
     */
    public $dispatchRequestMethod;
    /**
     * @var string
     */
    public $dispatchRequestPath;
    /**
     * @var string
     */
    public $dispatchRequestUri;

    /**
     * @var array<string, mixed>
     */
    public $dispatchActionAttributes = [];

    /**
     * @var array<string, RouterGenericHandlerMiddleware>
     */
    public $dispatchMiddlewareIndex = [];
    /**
     * @var array<string, RouterGenericHandlerFallback>
     */
    public $dispatchFallbackIndex = [];


    public function getId() : int
    {
        return $this->id;
    }


    public function __serialize() : array
    {
        $vars = get_object_vars($this);

        unset($vars[ 'dispatchActionAttributes' ]);

        unset($vars[ 'dispatchMiddlewareIndex' ]);
        unset($vars[ 'dispatchFallbackIndex' ]);

        unset($vars[ 'dispatchRequestMethod' ]);
        unset($vars[ 'dispatchRequestUri' ]);
        unset($vars[ 'dispatchRequestPath' ]);

        unset($vars[ 'dispatchContract' ]);

        return array_filter($vars);
    }

    public function __unserialize(array $data) : void
    {
        foreach ( $data as $key => $val ) {
            $this->{$key} = $val;
        }
    }

    public function serialize()
    {
        $array = $this->__serialize();

        return serialize($array);
    }

    public function unserialize($data)
    {
        $array = unserialize($data);

        $this->__unserialize($array);
    }
}
