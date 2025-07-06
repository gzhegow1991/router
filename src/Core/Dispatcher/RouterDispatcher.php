<?php

namespace Gzhegow\Router\Core\Dispatcher;

use Gzhegow\Lib\Lib;
use Gzhegow\Router\Router;
use Gzhegow\Router\RouterInterface;
use Gzhegow\Router\Core\Route\Route;
use Gzhegow\Router\Core\Node\RouterNode;
use Gzhegow\Lib\Modules\Php\Result\Result;
use Gzhegow\Router\Core\Config\RouterConfig;
use Gzhegow\Router\Exception\LogicException;
use Gzhegow\Lib\Modules\Func\Pipe\PipeContext;
use Gzhegow\Router\Exception\Runtime\NotFoundException;
use Gzhegow\Router\Core\Invoker\RouterInvokerInterface;
use Gzhegow\Router\Exception\Exception\DispatchException;
use Gzhegow\Router\Core\Collection\RouterRouteCollection;
use Gzhegow\Router\Core\Collection\RouterFallbackCollection;
use Gzhegow\Router\Core\Collection\RouterMiddlewareCollection;
use Gzhegow\Router\Core\Handler\Fallback\RouterGenericHandlerFallback;
use Gzhegow\Router\Core\Dispatcher\Contract\RouterDispatcherRouteContract;
use Gzhegow\Router\Core\Handler\Middleware\RouterGenericHandlerMiddleware;
use Gzhegow\Router\Core\Dispatcher\Contract\RouterDispatcherRequestContract;
use Gzhegow\Router\Core\Dispatcher\Contract\RouterDispatcherRouteContractInterface;
use Gzhegow\Router\Core\Dispatcher\Contract\RouterDispatcherRequestContractInterface;


class RouterDispatcher implements RouterDispatcherInterface
{
    /**
     * @var RouterConfig
     */
    protected $routerConfig;

    /**
     * @var RouterInvokerInterface
     */
    protected $routerInvoker;

    /**
     * @var RouterRouteCollection
     */
    protected $routeCollection;
    /**
     * @var RouterMiddlewareCollection
     */
    protected $middlewareCollection;
    /**
     * @var RouterFallbackCollection
     */
    protected $fallbackCollection;

    /**
     * @var RouterNode
     */
    protected $rootRouterNode;

    /**
     * @var string
     */
    protected $dispatchRequestMethod;
    /**
     * @var string
     */
    protected $dispatchRequestUri;
    /**
     * @var string
     */
    protected $dispatchRequestPath;

    /**
     * @var RouterDispatcherRequestContractInterface
     */
    protected $requestContract;
    /**
     * @var RouterDispatcherRouteContractInterface
     */
    protected $routeContract;

    /**
     * @var Route
     */
    protected $dispatchRoute;
    /**
     * @var array
     */
    protected $dispatchActionAttributes = [];

    /**
     * @var array<string, RouterGenericHandlerMiddleware>
     */
    protected $dispatchMiddlewareIndex = [];
    /**
     * @var array<string, RouterGenericHandlerFallback>
     */
    protected $dispatchFallbackIndex = [];


    public function initialize(RouterInterface $router) : void
    {
        $this->routerConfig = $router->getConfig();

        $this->routerInvoker = $router->getRouterInvoker();

        $this->routeCollection = $router->getRouteCollection();
        $this->middlewareCollection = $router->getMiddlewareCollection();
        $this->fallbackCollection = $router->getFallbackCollection();

        $this->rootRouterNode = $router->getRootRouterNode();
    }


    protected function resetRequest() : void
    {
        $this->dispatchRequestMethod = null;
        $this->dispatchRequestUri = null;
        $this->dispatchRequestPath = null;

        $this->requestContract = null;
    }

    protected function resetDispatch() : void
    {
        $this->dispatchRoute = null;
        $this->dispatchActionAttributes = [];

        $this->dispatchMiddlewareIndex = [];
        $this->dispatchFallbackIndex = [];
    }


    /**
     * @param mixed|RouterDispatcherRequestContractInterface $contract
     * @param array{ 0: array }|PipeContext                  $context
     *
     * @return mixed
     * @throws DispatchException
     */
    public function dispatch(
        $contract,
        $input = null,
        $context = null,
        array $args = []
    )
    {
        $contract = null
            ?? RouterDispatcherRequestContract::from($contract, $retCur = Result::asValue())
            ?? RouterDispatcherRouteContract::from($contract, $retCur = Result::asValue());

        if ($contract instanceof RouterDispatcherRequestContractInterface) {
            $result = $this->dispatchByRequest($contract);

        } elseif ($contract instanceof RouterDispatcherRouteContractInterface) {
            $result = $this->dispatchByRoute($contract);

        } else {
            throw new LogicException(
                [
                    ''
                    . 'The `contract` should be array or instance one of: '
                    . '[ '
                    . implode(' ][ ', [
                        RouterDispatcherRequestContract::class,
                        RouterDispatcherRouteContract::class,
                    ])
                    . ' ]',
                    //
                    $contract,
                ]
            );
        }

        return $result;
    }

    /**
     * @param array{ 0: array }|PipeContext $context
     *
     * @return mixed
     * @throws DispatchException
     */
    public function dispatchByRequest(
        RouterDispatcherRequestContractInterface $contract,
        $input = null,
        $context = null,
        array $args = []
    )
    {
        /**
         * @var RouterGenericHandlerMiddleware[] $dispatchMiddlewareList
         * @var RouterGenericHandlerFallback[]   $dispatchFallbackList
         */

        $theFunc = Lib::func();

        $this->resetRequest();
        $this->resetDispatch();

        $routerConfig = $this->routerConfig;

        $routeCollection = $this->routeCollection;
        $middlewareCollection = $this->middlewareCollection;
        $fallbackCollection = $this->fallbackCollection;

        $requestContract = $contract;

        $contractRequestMethod = $requestContract->getRequestMethod();
        $dispatchRequestMethod = $contractRequestMethod;
        if ($routerConfig->dispatchForceMethod) {
            $dispatchRequestMethod = $routerConfig->dispatchForceMethod;
        }

        $contractRequestUri = $requestContract->getRequestUri();
        $contractRequestPath = $requestContract->getRequestPath();
        $dispatchRequestUri = $contractRequestUri;
        $dispatchRequestPath = $contractRequestPath;
        if ($routerConfig->dispatchTrailingSlashMode) {
            $dispatchRequestPath = rtrim($dispatchRequestPath, '/');

            if ($routerConfig->dispatchTrailingSlashMode === Router::TRAILING_SLASH_ALWAYS) {
                $dispatchRequestPath = $dispatchRequestPath . '/';
            }

            if ('' === $dispatchRequestPath) {
                $dispatchRequestPath = '/';
            }
        }

        if ($dispatchRequestPath !== $contractRequestPath) {
            $dispatchRequestUri = $dispatchRequestPath;

            if ($contract->hasRequestQueryString($queryString)) {
                $dispatchRequestUri .= "?{$queryString}";
            }
            if ($contract->hasRequestFragment($fragment)) {
                $dispatchRequestUri .= "#{$fragment}";
            }
        }

        $this->requestContract = $requestContract;

        $this->dispatchRequestMethod = $dispatchRequestMethod;
        $this->dispatchRequestUri = $dispatchRequestUri;
        $this->dispatchRequestPath = $dispatchRequestPath;

        $dispatchRouteIndex = [];

        $dispatchActionAttributes = [];

        $routeNodeCurrent = $this->rootRouterNode;

        $routeSubpathCurrent = [ '' ];
        $routeSubpathList = [ '/' ];

        $split = $dispatchRequestPath;
        $split = ltrim($split, '/');
        $split = explode('/', $split);
        while ( [] !== $split ) {
            $requestUriPart = array_shift($split);

            $isLast = ([] === $split);

            if ($isLast) {
                if (isset($routeNodeCurrent->routeIndexByPart[ $requestUriPart ])) {
                    $dispatchRouteIndex = $routeNodeCurrent->routeIndexByPart[ $requestUriPart ];

                    break;
                }

                foreach ( $routeNodeCurrent->routeIndexByRegex as $regex => $routeIndex ) {
                    if (preg_match('/^' . $regex . '$/', $requestUriPart, $matches)) {
                        $dispatchRouteIndex = $routeIndex;

                        foreach ( $matches as $key => $value ) {
                            if (is_string($key)) {
                                $dispatchActionAttributes[ $key ] = $value;
                            }
                        }

                        break 2;
                    }
                }

                $routeSubpathCurrent[] = $requestUriPart;
                $routeSubpathList[] = implode('/', $routeSubpathCurrent);

            } else {
                if (isset($routeNodeCurrent->childNodeListByPart[ $requestUriPart ])) {
                    $routeNodeCurrent = $routeNodeCurrent->childNodeListByPart[ $requestUriPart ];

                    $routeSubpathCurrent[] = $routeNodeCurrent->part;
                    $routeSubpathList[] = implode('/', $routeSubpathCurrent);

                    continue;
                }

                foreach ( $routeNodeCurrent->childNodeListByRegex as $regex => $routeNode ) {
                    if (preg_match('/^' . $regex . '$/', $requestUriPart, $matches)) {
                        $routeNodeCurrent = $routeNode;

                        foreach ( $matches as $key => $value ) {
                            if (is_string($key)) {
                                $dispatchActionAttributes[ $key ] = $value;
                            }
                        }

                        $routeSubpathCurrent[] = $routeNodeCurrent->part;
                        $routeSubpathList[] = implode('/', $routeSubpathCurrent);

                        continue 2;
                    }
                }

                $routeNodeCurrent = null;

                break;
            }
        }

        $dispatchRouteId = null;
        if ([] !== $dispatchRouteIndex) {
            $intersect = [];

            $intersect[] = $dispatchRouteIndex;

            if (true
                && ! $routerConfig->dispatchIgnoreMethod
                && isset($routeNodeCurrent->routeIndexByMethod[ $dispatchRequestMethod ])
            ) {
                $intersect[] = $routeNodeCurrent->routeIndexByMethod[ $dispatchRequestMethod ];
            }

            $indexMatch = [];

            if (count($intersect) > 1) {
                $indexMatch = array_intersect_key(...$intersect);

            } elseif ([] !== $intersect) {
                $indexMatch = $intersect[ 0 ];
            }

            if ([] !== $indexMatch) {
                $dispatchRouteId = key($indexMatch);
            }
        }

        if (null !== $dispatchRouteId) {
            $dispatchRoute = $routeCollection->routeList[ $dispatchRouteId ];

            $dispatchRoute->requestContract = $requestContract;

            $dispatchRoute->dispatchRequestMethod = $dispatchRequestMethod;
            $dispatchRoute->dispatchRequestUri = $dispatchRequestUri;
            $dispatchRoute->dispatchRequestPath = $dispatchRequestPath;

            $routeContract = RouterDispatcherRouteContract::fromArray(
                [ $dispatchRoute, $dispatchActionAttributes ]
            );

            $result = $this->dispatchByRoute(
                $routeContract,
                $input,
                $context,
                $args
            );

        } else {
            $pipeContext = null;
            if (null !== $context) {
                if ($context instanceof PipeContext) {
                    $pipeContext = $context;

                } elseif (true
                    && is_array($context)
                    && isset($context[ 0 ])
                    && is_array($context[ 0 ])
                ) {
                    $pipeContext = new PipeContext($context[ 0 ]);

                } else {
                    throw new LogicException(
                        [
                            ''
                            . 'The `context` should be an array like `[ &$context ]` '
                            . 'or an instance of: ' . PipeContext::class,
                            //
                            $context,
                        ]
                    );
                }
            }

            $middlewareIndexes = [
                'path' => [],
            ];
            $fallbackIndexes = [
                'path' => [],
            ];

            foreach ( $routeSubpathList as $routeSubpath ) {
                if (isset($middlewareCollection->middlewareIndexByRoutePath[ $routeSubpath ])) {
                    $middlewareIndexes[ 'path' ][ $routeSubpath ] = $middlewareCollection->middlewareIndexByRoutePath[ $routeSubpath ];
                }

                if (isset($fallbackCollection->fallbackIndexByRoutePath[ $routeSubpath ])) {
                    $fallbackIndexes[ 'path' ][ $routeSubpath ] = $fallbackCollection->fallbackIndexByRoutePath[ $routeSubpath ];
                }
            }

            $middlewareIndex = [];
            foreach ( $middlewareIndexes[ 'path' ] as $index ) {
                $middlewareIndex += $index;
            }

            $fallbackIndex = [];
            foreach ( $fallbackIndexes[ 'path' ] as $index ) {
                $fallbackIndex += $index;
            }

            $dispatchMiddlewareList = [];
            $dispatchFallbackList = [];

            foreach ( $middlewareIndex as $i => $bool ) {
                $dispatchMiddlewareList[ $i ] = $middlewareCollection->middlewareList[ $i ];
            }

            foreach ( $fallbackIndex as $i => $bool ) {
                $dispatchFallbackList[ $i ] = $fallbackCollection->fallbackList[ $i ];
            }

            foreach ( $dispatchMiddlewareList as $middleware ) {
                $this->dispatchMiddlewareIndex[ $middleware->getKey() ] = true;
            }

            foreach ( $dispatchFallbackList as $fallback ) {
                $this->dispatchFallbackIndex[ $fallback->getKey() ] = true;
            }

            $fnPipelineCallGenericHandler = $this->fnPipelineCallGenericHandler();

            $pipeline = $theFunc->newPipe();
            $pipeline
                ->setContext($pipeContext)
                ->setFnCallUserFuncArray($fnPipelineCallGenericHandler)
            ;

            $pipelineChild = $pipeline;
            foreach ( $dispatchMiddlewareList as $middleware ) {
                $pipelineChild = $pipelineChild->middleware($middleware);
            }

            $throwable = new NotFoundException(
                ''
                . 'Route not found: '
                . '[ ' . $contractRequestUri . ' ]'
                . '[ ' . $contractRequestMethod . ' ]'
            );

            $pipelineChild->setThrowable($throwable);

            foreach ( $dispatchMiddlewareList as $devnull ) {
                $pipelineChild = $pipelineChild->endMiddleware();
            }

            foreach ( $dispatchFallbackList as $fallback ) {
                $pipeline->catch($fallback);
            }

            $pipelineArgs = $args;

            try {
                $result = $pipeline->run(
                    $input, $pipelineArgs
                );
            }
            catch ( \Gzhegow\Lib\Exception\Runtime\PipeException $e ) {
                throw new DispatchException(
                    [ 'Unhandled exception during dispatch' ], $e->getPrevious()
                );
            }
            catch ( \Throwable $e ) {
                throw new DispatchException(
                    [ 'Unhandled exception during dispatch' ], $e
                );
            }
        }

        return $result;
    }

    /**
     * @param array{ 0: array }|PipeContext $context
     *
     * @return mixed
     * @throws DispatchException
     */
    public function dispatchByRoute(
        RouterDispatcherRouteContractInterface $contract,
        $input = null,
        $context = null,
        array $args = []
    )
    {
        $theFunc = Lib::func();

        $middlewareCollection = $this->middlewareCollection;
        $fallbackCollection = $this->fallbackCollection;

        $pipeContext = null;
        if (null !== $context) {
            if ($context instanceof PipeContext) {
                $pipeContext = $context;

            } elseif (true
                && is_array($context)
                && isset($context[ 0 ])
                && is_array($context[ 0 ])
            ) {
                $pipeContext = new PipeContext($context[ 0 ]);

            } else {
                throw new LogicException(
                    [ 'The `context` should be an array like `[ &$context ]` or an instance of: ' . PipeContext::class, $context ]
                );
            }
        }

        $this->resetDispatch();

        $dispatchRouteClone = clone $contract->getRoute();
        $dispatchActionAttributes = $contract->getRouteAttributes();

        $routeId = $dispatchRouteClone->id;
        $routePath = $dispatchRouteClone->path;

        $routeSubpathList = [];
        $split = ltrim('/', $routePath);
        $split = explode('/', $split);
        while ( [] !== $split ) {
            $routeSubpathList[] = implode('/', $split);

            array_pop($split);
        }
        $routeSubpathList[] = '/';
        $routeSubpathList = array_reverse($routeSubpathList);

        $middlewareIndexes = [
            'id'   => [],
            'path' => [],
            'tag'  => [],
        ];
        $fallbackIndexes = [
            'id'   => [],
            'path' => [],
            'tag'  => [],
        ];

        if (isset($middlewareCollection->middlewareIndexByRouteId[ $routeId ])) {
            $middlewareIndexes[ 'id' ][ $routeId ] = $middlewareCollection->middlewareIndexByRouteId[ $routeId ];
        }

        if (isset($fallbackCollection->fallbackIndexByRouteId[ $routeId ])) {
            $fallbackIndexes[ 'id' ][ $routeId ] = $fallbackCollection->fallbackIndexByRouteId[ $routeId ];
        }

        foreach ( $routeSubpathList as $routeSubpath ) {
            if (isset($middlewareCollection->middlewareIndexByRoutePath[ $routeSubpath ])) {
                $middlewareIndexes[ 'path' ][ $routeSubpath ] = $middlewareCollection->middlewareIndexByRoutePath[ $routeSubpath ];
            }

            if (isset($fallbackCollection->fallbackIndexByRoutePath[ $routeSubpath ])) {
                $fallbackIndexes[ 'path' ][ $routeSubpath ] = $fallbackCollection->fallbackIndexByRoutePath[ $routeSubpath ];
            }
        }

        foreach ( $dispatchRouteClone->tagIndex as $tag => $bool ) {
            if (isset($middlewareCollection->middlewareIndexByRouteTag[ $tag ])) {
                $middlewareIndexes[ 'tag' ][ $tag ] = $middlewareCollection->middlewareIndexByRouteTag[ $tag ];
            }

            if (isset($fallbackCollection->fallbackIndexByRouteTag[ $tag ])) {
                $fallbackIndexes[ 'tag' ][ $tag ] = $fallbackCollection->fallbackIndexByRouteTag[ $tag ];
            }
        }

        $middlewareIndex = [];
        foreach ( $middlewareIndexes[ 'id' ] as $index ) {
            $middlewareIndex += $index;
        }
        foreach ( $middlewareIndexes[ 'path' ] as $index ) {
            $middlewareIndex += $index;
        }
        foreach ( $middlewareIndexes[ 'tag' ] as $index ) {
            $middlewareIndex += $index;
        }

        $fallbackIndex = [];
        foreach ( $fallbackIndexes[ 'id' ] as $index ) {
            $fallbackIndex += $index;
        }
        foreach ( $fallbackIndexes[ 'path' ] as $index ) {
            $fallbackIndex += $index;
        }
        foreach ( $fallbackIndexes[ 'tag' ] as $index ) {
            $fallbackIndex += $index;
        }

        $dispatchMiddlewareList = [];
        $dispatchFallbackList = [];

        foreach ( $middlewareIndex as $i => $bool ) {
            $dispatchMiddlewareList[ $i ] = $middlewareCollection->middlewareList[ $i ];
        }

        foreach ( $fallbackIndex as $i => $bool ) {
            $dispatchFallbackList[ $i ] = $fallbackCollection->fallbackList[ $i ];
        }

        $this->dispatchRoute = $dispatchRouteClone;
        $this->dispatchActionAttributes = $dispatchActionAttributes;

        $dispatchRouteClone->dispatchActionAttributes = $dispatchActionAttributes;

        foreach ( $dispatchMiddlewareList as $middleware ) {
            $dispatchRouteClone->dispatchMiddlewareIndex[ $middleware->getKey() ] = true;
        }

        foreach ( $dispatchFallbackList as $fallback ) {
            $dispatchRouteClone->dispatchFallbackIndex[ $fallback->getKey() ] = true;
        }

        $fnPipelineCallGenericHandler = $this->fnPipelineCallGenericHandler();

        $pipeline = $theFunc->newPipe();
        $pipeline
            ->setContext($pipeContext)
            ->setFnCallUserFuncArray($fnPipelineCallGenericHandler)
        ;

        $pipelineChild = $pipeline;
        foreach ( $dispatchMiddlewareList as $middleware ) {
            $pipelineChild = $pipelineChild->middleware($middleware);
        }

        $pipelineChild->map($dispatchRouteClone->action);

        foreach ( $dispatchMiddlewareList as $devnull ) {
            $pipelineChild = $pipelineChild->endMiddleware();
        }

        foreach ( $dispatchFallbackList as $fallback ) {
            $pipeline->catch($fallback);
        }

        $pipelineArgs = array_merge(
            [ $dispatchRouteClone ],
            $args
        );

        try {
            $result = $pipeline->run(
                $input, $pipelineArgs
            );
        }
        catch ( \Throwable $e ) {
            throw new DispatchException(
                [ 'Unhandled exception during dispatch', $e ], $e
            );
        }

        return $result;
    }


    public function hasRequestContract(?RouterDispatcherRequestContractInterface &$contract = null) : bool
    {
        $contract = null;

        if (null !== $this->requestContract) {
            $contract = $this->requestContract;

            return true;
        }

        return false;
    }

    public function getRequestContract() : RouterDispatcherRequestContractInterface
    {
        return $this->requestContract;
    }


    public function hasRouteContract(?RouterDispatcherRouteContractInterface &$contract = null) : bool
    {
        $contract = null;

        if (null !== $this->routeContract) {
            $contract = $this->routeContract;

            return true;
        }

        return false;
    }

    public function getRouteContract() : RouterDispatcherRouteContractInterface
    {
        return $this->routeContract;
    }


    public function getDispatchRequestMethod() : string
    {
        return $this->dispatchRequestMethod;
    }

    public function getDispatchRequestUri() : string
    {
        return $this->dispatchRequestUri;
    }

    public function getDispatchRequestPath() : string
    {
        return $this->dispatchRequestPath;
    }


    public function getDispatchRoute() : Route
    {
        return $this->dispatchRoute;
    }

    public function getDispatchActionAttributes() : array
    {
        return $this->dispatchActionAttributes;
    }


    /**
     * @return RouterGenericHandlerMiddleware[]
     */
    public function getDispatchMiddlewareIndex() : array
    {
        return $this->dispatchMiddlewareIndex;
    }

    /**
     * @return RouterGenericHandlerFallback[]
     */
    public function getDispatchFallbackIndex() : array
    {
        return $this->dispatchFallbackIndex;
    }


    public function fnPipelineCallGenericHandler()
    {
        $invoker = $this->routerInvoker;

        return static function ($fn, $args) use ($invoker) {
            array_unshift($args, $invoker);

            return call_user_func_array($fn, $args);
        };
    }
}
