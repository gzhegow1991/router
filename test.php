<?php

require_once __DIR__ . '/vendor/autoload.php';


// > настраиваем PHP
ini_set('memory_limit', '32M');


// > настраиваем обработку ошибок
error_reporting(E_ALL);
set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    if (error_reporting() & $errno) {
        throw new \ErrorException($errstr, -1, $errno, $errfile, $errline);
    }
});
set_exception_handler(function (\Throwable $e) {
    $current = $e;
    do {
        echo "\n";

        echo \Gzhegow\Router\Lib::php_var_dump($current) . "\n";
        echo $current->getMessage() . "\n";

        foreach ( $e->getTrace() as $traceItem ) {
            echo "{$traceItem['file']} : {$traceItem['line']}" . "\n";
        }

        echo "\n";
    } while ( $current = $current->getPrevious() );

    die();
});


// > добавляем несколько функция для тестирования
// > добавляем несколько функция для тестирования
function _dump($value, ...$values) : void
{
    echo \Gzhegow\Router\Lib::php_dump($value, ...$values) . "\n";
}

function _eol(string $str) : string
{
    return implode("\n", explode(PHP_EOL, $str));
}

function _error($message, $code = -1, $previous = null, string $file = null, int $line = null)
{
    $e = new \Gzhegow\Pipeline\Exception\RuntimeException($message, $code, $previous);

    if (($file !== null) && ($line !== null)) {
        (function ($file, $line) {
            $this->file = $file;
            $this->line = $line;
        })->call($e, $file, $line);
    }

    return $e;
}

function _assert_call(\Closure $fn, array $exResult = null, string $exOutput = null) : void
{
    $exResult = $exResult ?? [];

    $mt = microtime(true);

    $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);

    ob_start();
    $result = $fn();
    $output = ob_get_clean();

    $output = trim($output);
    $output = str_replace("\r\n", "\n", $output);
    $output = preg_replace('/' . preg_quote('\\', '/') . '+/', '\\', $output);

    if (count($exResult)) {
        [ $_exResult ] = $exResult;

        if ($_exResult !== $result) {
            var_dump($result);

            throw _error(
                'Test result check failed', -1, null,
                $trace[ 0 ][ 'file' ], $trace[ 0 ][ 'line' ]
            );
        }
    }

    if (null !== $exOutput) {
        if (_eol($exOutput) !== _eol($output)) {
            print_r($output);

            throw _error(
                'Test output check failed', -1, null,
                $trace[ 0 ][ 'file' ], $trace[ 0 ][ 'line' ]
            );
        }

        echo $exOutput . "\n";
    }

    $mtDiffSeconds = round(microtime(true) - $mt, 6);
    $mtDiffSeconds .= 's';

    echo 'Test OK - ' . $mtDiffSeconds . "\n\n";
}


// >>> ЗАПУСКАЕМ!

// >>> сначала всегда фабрика
$factory = new \Gzhegow\Router\RouterFactory();

// >>> создаем роутер
$router = $factory->newRouter();

// >>> настраиваем роутер
$settings = [
    // > позволить регистрировать callable с объектами и \Closure, кеш в этом случае работать не будет
    $registerAllowObjectsAndClosures = false,
    //
    // > работа с trailing-slash при регистрации маршрута (бросить исключение)
    $compileTrailingSlashMode = \Gzhegow\Router\Router::TRAILING_SLASH_AS_IS,
    // Router::TRAILING_SLASH_AS_IS // > оставить как есть
    // Router::TRAILING_SLASH_ALWAYS, // > добавлять trailing-slash
    // Router::TRAILING_SLASH_NEVER, // > удалить trailing-slash
    //
    // > не учитывать метод при вызове dispatch(), в этом случае POST действия будут отрабатывать даже на запросы из браузера (однако порядок регистрации важен, если на том же маршруте GET/POST, то отработает тот что раньше зарегистрирован)
    $dispatchIgnoreMethod = false,
    //
    // > подменить метод при вызове dispatch(), например, несмотря на GET запрос выполнить действие, зарегистрированное на POST
    $dispatchForceMethod = null, // HttpMethod::METHOD_POST | HttpMethod::METHOD_GET | HttpMethod::METHOD_PUT | HttpMethod::METHOD_OPTIONS | etc.
    //
    // > работа с trailing-slash при вызове dispatch() (обеспечив совпадение или 404 exception)
    $dispatchTrailingSlashMode = \Gzhegow\Router\Router::TRAILING_SLASH_AS_IS,  // TRAILING_SLASH_ALWAYS | TRAILING_SLASH_NEVER
    // Router::TRAILING_SLASH_AS_IS // > оставить как есть
    // Router::TRAILING_SLASH_ALWAYS, // > добавлять trailing-slash
    // Router::TRAILING_SLASH_NEVER, // > удалить trailing-slash
];
$router->setSettings(...$settings);

// >>> настраиваем кеш роутера
$cacheDir = __DIR__ . '/var/cache';
$cacheNamespace = 'gzhegow.router';

// >>> для кэша можно использовать путь к файлу, в этом случае кеш будет сделан через file_{get|put}_contents() + (un)serialize()
$cacheDirpath = "{$cacheDir}/{$cacheNamespace}";
$cacheFilename = "router.cache";
$router->setCacheSettings([
    // 'cacheMode'     => Reflector::CACHE_MODE_NO_CACHE, // > не использовать кеш совсем
    'cacheMode'     => \Gzhegow\Router\Cache\RouterCache::CACHE_MODE_STORAGE, // > использовать файловую систему или адаптер (хранилище)
    //
    'cacheDirpath'  => $cacheDirpath,
    'cacheFilename' => $cacheFilename,
]);

// >>> либо можно установить пакет `composer require symfony/cache` и использовать адаптер, чтобы хранить кэш в redis или любым другим способом
// $symfonyCacheAdapter = new \Symfony\Component\Cache\Adapter\FilesystemAdapter(
//     $cacheNamespace, $defaultLifetime = 0, $cacheDir
// );
// $redisClient = \Symfony\Component\Cache\Adapter\RedisAdapter::createConnection('redis://localhost');
// $symfonyCacheAdapter = new \Symfony\Component\Cache\Adapter\RedisAdapter(
//     $redisClient,
//     $cacheNamespace = '',
//     $defaultLifetime = 0
// );
// $router->setCacheSettings([
//     'cacheMode'    => Reflector::CACHE_MODE_STORAGE,
//     'cacheAdapter' => $symfonyCacheAdapter,
// ]);

// >>> вызываем функцию, которая загрузит кеш, и если его нет - выполнит регистрацию маршрутов и сохранение их в кэш (не обязательно)
$router->cacheRemember(static function (\Gzhegow\Router\RouterInterface $router) {
    // > добавляем паттерн, который можно использовать в маршрутах
    $router->pattern('{id}', '[0-9]+');

    // > для того, чтобы зарегистрировать маршруты удобно использовать группировку
    // > группировка работает только на 1 уровень вложенности, вложить "группу в группу" нельзя, это решение осознанное, чтобы не вводить команду в ступор, пытаясь понять что и куда идет
    // $group = $router->group();

    // > каждый множественный метод имеет аналог в единственном числе и аналог, который перезаписывает предыдущие установки
    // $group->httpMethods([]);
    // $group->httpMethod('');
    // $group->setHttpMethods([]);
    //
    // $group->setMiddlewares([]);
    // $group->middlewares([]);
    // $group->middleware('');
    //
    // $group->setFallbacks([]);
    // $group->fallbacks([]);
    // $group->fallback('');
    //
    // $group->setTags([]);
    // $group->tags([]);
    // $group->tag('');

    // > добавим группу маршрутов
    $group = $router->group()
        // > ставим теги для каждого роута в группе
        // > с помощью тегов можно будет найти нужную группу роутов или настроить её поведение, как показано выше
        ->tags([ 'user' ])
        //
        // > подключаем поставляемый middleware-посредник для CORS-заголовков
        // > в поставляемом посреднике режим "разрешить всё", если нужны тонкие настройки - стоит наследовать класс или написать свой
        ->middlewares([
            '\Gzhegow\Router\Handler\Demo\Middleware\DemoCorsMiddleware',
            '\Gzhegow\Router\Handler\Demo\Middleware\Demo1stMiddleware',
            '\Gzhegow\Router\Handler\Demo\Middleware\Demo2ndMiddleware',
        ])
        //
        // > этот fallback-обработчик написан обрабатывать любые \Throwable, превращая их строку текста
        ->fallbacks([
            '\Gzhegow\Router\Handler\Demo\Fallback\DemoRuntimeExceptionFallback',
            '\Gzhegow\Router\Handler\Demo\Fallback\DemoLogicExceptionFallback',
            '\Gzhegow\Router\Handler\Demo\Fallback\DemoThrowableFallback',
        ])
        //
        // регистрируем группу
        ->register(static function (\Gzhegow\Router\Route\RouteGroup $group) {
            // > пример регистрации маршрута
            // $group
            //     ->route(
            //         $path = '/api/v1/user/{id}/main',
            //         $httpMethods = [ 'GET' ],
            //         $action = '\Gzhegow\Router\Handler\Demo\Action\DemoCorsAction',
            //         $name = 'user.main',
            //         $tags = [ 'user' ]
            //     )
            //     ->name($name = 'user.main')
            //     ->tags($tags = [ 'user' ])
            // ;

            // > можно указывать теги и поведение и для конкретного роута, при этом если такие же поведения уже были зарегистрированы раньше, то повторно они не добавятся
            // $group->route('/api/v1/user/{id}/main', 'GET', '\Gzhegow\Router\Handler\Demo\Action\DemoCorsAction')
            //     ->name('user.main')
            //     ->tags([ 'user' ])
            //     ->middlewares([
            //         '\Gzhegow\Router\Handler\Demo\Middleware\DemoCorsMiddleware',
            //         '\Gzhegow\Router\Handler\Demo\Middleware\Demo1stMiddleware',
            //         '\Gzhegow\Router\Handler\Demo\Middleware\Demo2ndMiddleware',
            //     ])
            //     ->fallbacks([
            //         '\Gzhegow\Router\Handler\Demo\Fallback\DemoRuntimeExceptionFallback',
            //         '\Gzhegow\Router\Handler\Demo\Fallback\DemoLogicExceptionFallback',
            //         '\Gzhegow\Router\Handler\Demo\Fallback\DemoThrowableFallback',
            //     ])
            // ;

            $group->route('/api/v1/user/{id}/main', 'GET', [ '\Gzhegow\Router\Handler\Demo\Controller\DemoController', 'mainGet' ], 'user.main');
            // > это же имя мы уже использовали выше, однако `path` совпадает и так можно
            $group->route('/api/v1/user/{id}/main', 'POST', [ '\Gzhegow\Router\Handler\Demo\Controller\DemoController', 'mainPost' ], 'user.main');
            $group->route('/api/v1/user/{id}/main', 'OPTIONS', '\Gzhegow\Router\Handler\Demo\Action\DemoCorsAction', 'user.main');

            // > можно задавать группу в группе, метод ->register() передаст все роуты в родительскую группу и соединит групповые настройки с основными
            $group->group()
                ->name('user.main')
                ->path('/api/v1/user/{id}')
                ->register(static function (\Gzhegow\Router\Route\RouteGroup $group) {
                    $group->route('/logic', 'GET', [ '\Gzhegow\Router\Handler\Demo\Controller\DemoController', 'logic' ], 'user.logic');
                    $group->route('/runtime', 'GET', [ '\Gzhegow\Router\Handler\Demo\Controller\DemoController', 'runtime' ], 'user.runtime');
                })
            ;
        })
    ;

    // > можно добавлять маршруты и без групп (в главную группу)
    // $router->route('/api/v1/user/{id}/main', 'GET', [ '\Gzhegow\Router\Handler\Demo\Controller\DemoController', 'mainGet' ], 'user.main');

    // > можно добавлять роуты на основе "чертежа" (это неявно используется во всех остальных способах)
    // $blueprint = $router->blueprint()
    //     ->path('/api/v1/user/{id}/main')
    //     ->httpMethod('GET')
    //     ->action([ '\Gzhegow\Router\Handler\Demo\Controller\DemoController', 'main' ])
    // ;
    // $router->addRoute($blueprint);

    // > добавляет middleware-посредник по пути (они отработают даже если маршрут не найден, но путь начинался с указанного)
    // > будьте внимательны, посредники отрабатывают в той последовательности, в которой заданы, если задать их до группы, то и отработают они раньше
    $router->middlewareOnPath('/api/v1/user', '\Gzhegow\Router\Handler\Demo\Middleware\DemoCorsMiddleware');
    $router->middlewareOnPath('/api/v1/user', '\Gzhegow\Router\Handler\Demo\Middleware\Demo1stMiddleware');
    $router->middlewareOnPath('/api/v1/user', '\Gzhegow\Router\Handler\Demo\Middleware\Demo2ndMiddleware');
    // // > можно привязывать посредники так же по тегу, теги в свою очередь привязывать к маршрутам
    // $router->middlewareOnTag('user', '\Gzhegow\Router\Handler\Demo\Middleware\DemoCorsMiddleware');

    // > добавляет fallback-обработчик по пути (если во время действия будет брошено исключение или роута не будет - запустится это действие)
    // > несколько fallback-обработчиков запустятся один за другим, пока какой-либо из них не вернет not-NULL результат, и если ни один - будет брошено \Gzhegow\Pipeline\PipelineException
    // > будьте внимательны, fallback-обработчики отрабатывают в той последовательности, в которой заданы, если задать их до группы, то и отработают они раньше
    $router->fallbackOnPath('/api/v1/user', '\Gzhegow\Router\Handler\Demo\Fallback\DemoThrowableFallback');
    // // > можно привязывать fallback-обработчики так же по тегу, теги в свою очередь привязывать к маршрутам
    // $router->fallbackOnTag('user', '\Gzhegow\Router\Handler\Demo\Fallback\DemoThrowableFallback');

    // > коммитим указанные выше роуты - это скомпилирует роуты (при компиляции роуты индексируются для быстрого поиска)
    $router->commit();
});


// > TEST
// > так можно искать маршруты с помощью имен или тегов
// > первый результат
// $route = $router->matchFirstByName('user.main');
// $route = $router->matchFirstByTag('user');
$fn = function () use ($router) {
    _dump('TEST 1');

    $ids = [ 1, 2 ];
    $names = [ 'user.main' ];
    $tags = [ 'user' ];


    $batch = $router->matchAllByIds($ids);
    foreach ( $batch as $i => $routes ) {
        _dump('[ RESULT ]', $i, $routes);
    }

    $batch = $router->matchAllByNames($names);
    foreach ( $batch as $i => $routes ) {
        _dump('[ RESULT ]', $i, $routes);
    }

    $batch = $router->matchAllByTags($tags);
    foreach ( $batch as $i => $routes ) {
        _dump('[ RESULT ]', $i, $routes);
    }


    $route = $router->matchFirstByIds($ids);
    _dump('[ RESULT ]', $route);

    $route = $router->matchFirstByNames($names);
    _dump('[ RESULT ]', $route);

    $route = $router->matchFirstByTags($tags);
    _dump('[ RESULT ]', $route);

    _dump('');
};
_assert_call($fn, [], <<<HEREDOC
"TEST 1"
"[ RESULT ]" | 1 | { object(Gzhegow\Router\Route\Route) }
"[ RESULT ]" | 2 | { object(Gzhegow\Router\Route\Route) }
"[ RESULT ]" | 0 | [ 1 => { object(Gzhegow\Router\Route\Route) }, 2 => { object(Gzhegow\Router\Route\Route) }, 3 => { object(Gzhegow\Router\Route\Route) } ]
"[ RESULT ]" | 0 | [ 1 => { object(Gzhegow\Router\Route\Route) }, 2 => { object(Gzhegow\Router\Route\Route) }, 3 => { object(Gzhegow\Router\Route\Route) }, 4 => { object(Gzhegow\Router\Route\Route) }, 5 => { object(Gzhegow\Router\Route\Route) } ]
"[ RESULT ]" | { object(Gzhegow\Router\Route\Route) }
"[ RESULT ]" | { object(Gzhegow\Router\Route\Route) }
"[ RESULT ]" | { object(Gzhegow\Router\Route\Route) }
""
HEREDOC
);

// // > TEST
// // > так можно искать маршруты с помощью нескольких фильтров (если указать массивы - они работают как логическое ИЛИ, тогда как сами фильтры работают через логическое И
$fn = function () use ($router) {
    _dump('TEST 2');

    $contract = \Gzhegow\Router\Contract\RouterMatchContract::from([
        // 'id'          => 1,
        // 'ids'         => [ 1 ],
        // 'path'        => '/api/v1/user/{id}',
        // 'pathes'      => [ '/api/v1/user/{id}' ],
        // 'httpMethod'  => 'GET',
        // 'httpMethods' => [ 'GET' ],
        // 'name'        => 'user.main',
        // 'names'       => [ 'user.main' ],
        // 'tag'         => 'user',
        // 'tags'        => [ 'user' ],
        //
        'name'        => 'user.main',
        'tag'         => 'user',
        'httpMethods' => [ 'GET', 'POST' ],
    ]);

    $routes = $router->matchByContract($contract);

    foreach ( $routes as $id => $route ) {
        _dump('[ RESULT ]', $id, $route);
    }

    _dump('');
};
_assert_call($fn, [], <<<HEREDOC
"TEST 2"
"[ RESULT ]" | 1 | { object(Gzhegow\Router\Route\Route) }
"[ RESULT ]" | 2 | { object(Gzhegow\Router\Route\Route) }
""
HEREDOC
);

// > TEST
// > так можно сгенерировать ссылки для зарегистрированных маршрутов
$fn = function () use ($router) {
    _dump('TEST 3');

    $instances = [];
    $instances[ 'a' ] = $router->matchFirstByNames('user.main');
    //
    $names = [];
    $names[ 'b' ] = 'user.main';
    $names[ 'c' ] = 'user.main';
    //
    $routes = $instances + $names;
    //
    $ids = [];
    $ids[ 'a' ] = 1;
    $ids[ 'b' ] = 2;
    $ids[ 'c' ] = 3;
    //
    $attributes = [];
    $attributes[ 'id' ] = $ids;
    //
    // > можно передать либо список объектов (instance of Route::class) и/или список строк (route `name`)
    $result = $router->urls($routes, $attributes);

    _dump('[ RESULT ]', $result);

    _dump('');
};
_assert_call($fn, [], <<<HEREDOC
"TEST 3"
"[ RESULT ]" | [ "a" => "/api/v1/user/1/main", "b" => "/api/v1/user/2/main", "c" => "/api/v1/user/3/main" ]
""
HEREDOC
);

// > TEST
// > так можно запустить выполнение маршрута в вашем файле index.php, на который указывает apache2/nginx
$fn = function () use ($router) {
    _dump('TEST 4');

    $contract = \Gzhegow\Router\Contract\RouterDispatchContract::from([ 'GET', '/api/v1/user/1/main' ]);

    $result = $router->dispatch($contract);

    _dump('[ RESULT ]', $result);

    _dump('');
};
_assert_call($fn, [], <<<HEREDOC
"TEST 4"
@before :: Gzhegow\Router\Handler\Demo\Middleware\DemoCorsMiddleware::__invoke
@before :: Gzhegow\Pipeline\Handler\Demo\Middleware\Demo1stMiddleware::__invoke
@before :: Gzhegow\Pipeline\Handler\Demo\Middleware\Demo2ndMiddleware::__invoke
Gzhegow\Router\Handler\Demo\Controller\DemoController::mainGet
@after :: Gzhegow\Pipeline\Handler\Demo\Middleware\Demo2ndMiddleware::__invoke
@after :: Gzhegow\Pipeline\Handler\Demo\Middleware\Demo1stMiddleware::__invoke
@after :: Gzhegow\Router\Handler\Demo\Middleware\DemoCorsMiddleware::__invoke
"[ RESULT ]" | 1
""
HEREDOC
);

// > TEST
// > такого маршрута нет, запустится ранее указанный fallback-обработчик
$fn = function () use ($router) {
    _dump('TEST 5');

    $contract = \Gzhegow\Router\Contract\RouterDispatchContract::from([ 'GET', '/api/v1/user/not-found' ]);

    $result = $router->dispatch($contract);

    _dump('[ RESULT ]', $result);

    _dump('');
};
_assert_call($fn, [], <<<HEREDOC
"TEST 5"
@before :: Gzhegow\Router\Handler\Demo\Middleware\DemoCorsMiddleware::__invoke
@before :: Gzhegow\Pipeline\Handler\Demo\Middleware\Demo1stMiddleware::__invoke
@before :: Gzhegow\Pipeline\Handler\Demo\Middleware\Demo2ndMiddleware::__invoke
Gzhegow\Pipeline\Handler\Demo\Fallback\DemoThrowableFallback::__invoke
@after :: Gzhegow\Pipeline\Handler\Demo\Middleware\Demo2ndMiddleware::__invoke
@after :: Gzhegow\Pipeline\Handler\Demo\Middleware\Demo1stMiddleware::__invoke
@after :: Gzhegow\Router\Handler\Demo\Middleware\DemoCorsMiddleware::__invoke
"[ RESULT ]" | "Gzhegow\Pipeline\Handler\Demo\Fallback\DemoThrowableFallback::__invoke result."
""
HEREDOC
);

// > TEST
// > такого маршрута нет, и одновременно с этим обработчик ошибок не был задан (либо был задан, но вернул NULL, что трактуется как "обработка не удалась")
$fn = function () use ($router) {
    _dump('TEST 6');

    $contract = \Gzhegow\Router\Contract\RouterDispatchContract::from([ 'GET', '/api/v1/not-found/not-found' ]);

    $result = null;
    try {
        $result = $router->dispatch($contract);
    }
    catch ( \Gzhegow\Router\Exception\Exception\DispatchException $e ) {
        _dump('[ CATCH ]', get_class($e), $e->getMessage());

        foreach ( $e->getPreviousStack() as $ee ) {
            _dump('[ CATCH ]', get_class($ee), $ee->getMessage());
        }
    }
    _dump('[ RESULT ]', $result);

    _dump('');
};
_assert_call($fn, [], <<<HEREDOC
"TEST 6"
"[ CATCH ]" | "Gzhegow\Router\Exception\Exception\DispatchException" | "Unhandled exception occured during dispatch"
"[ CATCH ]" | "Gzhegow\Router\Exception\Runtime\NotFoundException" | "Route not found: `/api/v1/not-found/not-found` / `GET`"
"[ RESULT ]" | NULL
""
HEREDOC
);

// > TEST
// > этот маршрут бросает \LogicException, запустятся DemoLogicExceptionFallback и DemoThrowableFallback
$fn = function () use ($router) {
    _dump('TEST 7');

    $contract = \Gzhegow\Router\Contract\RouterDispatchContract::from([ 'GET', '/api/v1/user/1/logic' ]);

    $result = $router->dispatch($contract);

    _dump('[ RESULT ]', $result);

    _dump('');
};
_assert_call($fn, [], <<<HEREDOC
"TEST 7"
@before :: Gzhegow\Router\Handler\Demo\Middleware\DemoCorsMiddleware::__invoke
@before :: Gzhegow\Pipeline\Handler\Demo\Middleware\Demo1stMiddleware::__invoke
@before :: Gzhegow\Pipeline\Handler\Demo\Middleware\Demo2ndMiddleware::__invoke
Gzhegow\Router\Handler\Demo\Controller\DemoController::logic
Gzhegow\Pipeline\Handler\Demo\Fallback\DemoLogicExceptionFallback::__invoke
@after :: Gzhegow\Pipeline\Handler\Demo\Middleware\Demo2ndMiddleware::__invoke
@after :: Gzhegow\Pipeline\Handler\Demo\Middleware\Demo1stMiddleware::__invoke
@after :: Gzhegow\Router\Handler\Demo\Middleware\DemoCorsMiddleware::__invoke
"[ RESULT ]" | "Gzhegow\Pipeline\Handler\Demo\Fallback\DemoLogicExceptionFallback::__invoke result."
""
HEREDOC
);

// > TEST
// > этот маршрут бросает \RuntimeException, запустятся DemoThrowableFallback и DemoRuntimeExceptionFallback
$fn = function () use ($router) {
    _dump('TEST 8');

    $contract = \Gzhegow\Router\Contract\RouterDispatchContract::from([ 'GET', '/api/v1/user/1/runtime' ]);

    $result = $router->dispatch($contract);

    _dump('[ RESULT ]', $result);

    _dump('');
};
_assert_call($fn, [], <<<HEREDOC
"TEST 8"
@before :: Gzhegow\Router\Handler\Demo\Middleware\DemoCorsMiddleware::__invoke
@before :: Gzhegow\Pipeline\Handler\Demo\Middleware\Demo1stMiddleware::__invoke
@before :: Gzhegow\Pipeline\Handler\Demo\Middleware\Demo2ndMiddleware::__invoke
Gzhegow\Router\Handler\Demo\Controller\DemoController::runtime
Gzhegow\Pipeline\Handler\Demo\Fallback\DemoRuntimeExceptionFallback::__invoke
@after :: Gzhegow\Pipeline\Handler\Demo\Middleware\Demo2ndMiddleware::__invoke
@after :: Gzhegow\Pipeline\Handler\Demo\Middleware\Demo1stMiddleware::__invoke
@after :: Gzhegow\Router\Handler\Demo\Middleware\DemoCorsMiddleware::__invoke
"[ RESULT ]" | "Gzhegow\Pipeline\Handler\Demo\Fallback\DemoRuntimeExceptionFallback::__invoke result."
""
HEREDOC
);
