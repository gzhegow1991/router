<?php

require_once __DIR__ . '/vendor/autoload.php';


// > настраиваем PHP
ini_set('memory_limit', '32M');


// > настраиваем обработку ошибок
(new \Gzhegow\Lib\Exception\ErrorHandler())
    ->useErrorReporting()
    ->useErrorHandler()
    ->useExceptionHandler()
;


// > добавляем несколько функция для тестирования
function _values($separator = null, ...$values) : string
{
    return \Gzhegow\Lib\Lib::debug()->values($separator, [], ...$values);
}

function _print(...$values) : void
{
    echo _values(' | ', ...$values) . PHP_EOL;
}

function _assert_stdout(
    \Closure $fn, array $fnArgs = [],
    string $expectedStdout = null
) : void
{
    $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);

    \Gzhegow\Lib\Lib::test()->assertStdout(
        $trace,
        $fn, $fnArgs,
        $expectedStdout
    );
}


// >>> ЗАПУСКАЕМ!

// > сначала всегда фабрика
$factory = new \Gzhegow\Router\Core\RouterFactory();

// > создаем конфигурацию
$config = new \Gzhegow\Router\Core\RouterConfig();
$config->configure(function (\Gzhegow\Router\Core\RouterConfig $config) {
    // >>> роутер
    $config->registerAllowObjectsAndClosures = false;
    $config->compileTrailingSlashMode = \Gzhegow\Router\Core\Router::TRAILING_SLASH_AS_IS;
    $config->dispatchIgnoreMethod = false;
    $config->dispatchForceMethod = null;
    $config->dispatchTrailingSlashMode = \Gzhegow\Router\Core\Router::TRAILING_SLASH_AS_IS;

    // >>> кэш роутера
    $config->cache->cacheMode = \Gzhegow\Router\Core\Cache\RouterCache::CACHE_MODE_STORAGE;
    //
    $cacheDir = __DIR__ . '/var/cache';
    $cacheNamespace = 'gzhegow.router';
    $cacheDirpath = "{$cacheDir}/{$cacheNamespace}";
    $cacheFilename = "router.cache";
    $config->cache->cacheDirpath = $cacheDirpath;
    $config->cache->cacheFilename = $cacheFilename;
    //
    // $symfonyCacheAdapter = new \Symfony\Component\Cache\Adapter\FilesystemAdapter(
    //     $cacheNamespace, $defaultLifetime = 0, $cacheDir
    // );
    // $redisClient = \Symfony\Component\Cache\Adapter\RedisAdapter::createConnection('redis://localhost');
    // $symfonyCacheAdapter = new \Symfony\Component\Cache\Adapter\RedisAdapter(
    //     $redisClient,
    //     $cacheNamespace = '',
    //     $defaultLifetime = 0
    // );
    // $config->cache->cacheMode = \Gzhegow\Router\Core\Cache\RouterCache::CACHE_MODE_STORAGE;
    // $config->cache->cacheAdapter = $symfonyCacheAdapter;
});

// > создаем кеш роутера
// > его задача сохранять маршруты в файл после того, как они будут скомпилированы и сохранены в виде дерева
$cache = new \Gzhegow\Router\Core\Cache\RouterCache($config->cache);

// > создаем фабрику для конвеера
$pipelineFactory = new \Gzhegow\Router\Package\Gzhegow\Pipeline\RouterPipelineFactory();

// > создаем процессор для конвеера
$pipelineProcessor = new \Gzhegow\Router\Package\Gzhegow\Pipeline\Processor\RouterProcessor(
    $pipelineFactory
);

// > создаем процесс-менеджер для конвеера
$pipelineProcessManager = new \Gzhegow\Router\Package\Gzhegow\Pipeline\ProcessManager\RouterProcessManager(
    $pipelineFactory,
    $pipelineProcessor
);

// > создаем роутер
$router = new \Gzhegow\Router\Core\Router(
    $factory,
    $cache,
    //
    $pipelineFactory,
    $pipelineProcessManager,
    //
    $config
);

// > так можно очистить кэш
// $router->cacheClear();

// > вызываем функцию, которая загрузит кеш, и если его нет - выполнит регистрацию маршрутов и сохранение их в кэш (не обязательно)
$router->cacheRemember(static function (\Gzhegow\Router\Core\RouterInterface $router) {
    // > добавляем паттерн, который можно использовать в маршрутах
    $router->pattern('{id}', '[0-9]+');

    // > для того, чтобы зарегистрировать маршруты удобно использовать группировку
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
    $router->group()
        // > ставим теги для каждого роута в группе
        // > с помощью тегов можно будет найти нужную группу роутов или настроить её поведение, как показано выше
        ->tags([ 'user' ])
        //
        // > подключаем поставляемый middleware-посредник для CORS-заголовков
        // > в поставляемом посреднике режим "разрешить всё", если нужны тонкие настройки - стоит наследовать класс или написать свой
        ->middlewares([
            '\Gzhegow\Router\Demo\Handler\Middleware\DemoCorsMiddleware',
            '\Gzhegow\Router\Demo\Handler\Middleware\Demo1stMiddleware',
            '\Gzhegow\Router\Demo\Handler\Middleware\Demo2ndMiddleware',
        ])
        //
        // > этот fallback-обработчик написан обрабатывать любые \Throwable, превращая их строку текста
        ->fallbacks([
            '\Gzhegow\Router\Demo\Handler\Fallback\DemoRuntimeExceptionFallback',
            '\Gzhegow\Router\Demo\Handler\Fallback\DemoLogicExceptionFallback',
            '\Gzhegow\Router\Demo\Handler\Fallback\DemoThrowableFallback',
        ])
        //
        // регистрируем группу
        ->register(static function (\Gzhegow\Router\Core\Route\RouteGroup $group) {
            // > пример регистрации маршрута
            // $group
            //     ->route(
            //         $path = '/api/v1/user/{id}/main',
            //         $httpMethods = [ 'GET' ],
            //         $action = '\Gzhegow\Router\Demo\Handler\Action\DemoCorsAction',
            //         $name = 'user.main',
            //         $tags = [ 'user' ]
            //     )
            //     ->name($name = 'user.main')
            //     ->tags($tags = [ 'user' ])
            // ;

            // > можно указывать теги и поведение и для конкретного роута, при этом если такие же поведения уже были зарегистрированы раньше, то повторно они не добавятся
            // $group->route('/api/v1/user/{id}/main', 'GET', '\Gzhegow\Router\Demo\Handler\Action\DemoCorsAction')
            //     ->name('user.main')
            //     ->tags([ 'user' ])
            //     ->middlewares([
            //         '\Gzhegow\Router\Demo\Handler\Middleware\DemoCorsMiddleware',
            //         '\Gzhegow\Router\Demo\Handler\Middleware\Demo1stMiddleware',
            //         '\Gzhegow\Router\Demo\Handler\Middleware\Demo2ndMiddleware',
            //     ])
            //     ->fallbacks([
            //         '\Gzhegow\Router\Demo\Handler\Fallback\DemoRuntimeExceptionFallback',
            //         '\Gzhegow\Router\Demo\Handler\Fallback\DemoLogicExceptionFallback',
            //         '\Gzhegow\Router\Demo\Handler\Fallback\DemoThrowableFallback',
            //     ])
            // ;

            $group->route('/api/v1/user/{id}/main', 'GET', [ '\Gzhegow\Router\Demo\Handler\Controller\DemoController', 'mainGet' ], 'user.main');
            // > это же имя мы уже использовали выше, однако `path` совпадает и так можно
            $group->route('/api/v1/user/{id}/main', 'POST', [ '\Gzhegow\Router\Demo\Handler\Controller\DemoController', 'mainPost' ], 'user.main');
            $group->route('/api/v1/user/{id}/main', 'OPTIONS', '\Gzhegow\Router\Demo\Handler\Action\DemoCorsAction', 'user.main');

            // > можно задавать группу в группе, метод ->register() передаст все роуты в родительскую группу и соединит групповые настройки с основными
            $group->group()
                ->name('user.main')
                ->path('/api/v1/user/{id}')
                ->register(static function (\Gzhegow\Router\Core\Route\RouteGroup $group) {
                    $group->route('/logic', 'GET', [ '\Gzhegow\Router\Demo\Handler\Controller\DemoController', 'logic' ], 'user.logic');
                    $group->route('/runtime', 'GET', [ '\Gzhegow\Router\Demo\Handler\Controller\DemoController', 'runtime' ], 'user.runtime');
                })
            ;
        })
    ;

    // > можно добавлять маршруты и без групп (в главную группу)
    // $router->route('/api/v1/user/{id}/main', 'GET', [ '\Gzhegow\Router\Demo\Handler\Controller\DemoController', 'mainGet' ], 'user.main');

    // > можно добавлять роуты на основе "чертежа" (это неявно используется во всех остальных способах)
    // $blueprint = $router->blueprint()
    //     ->path('/api/v1/user/{id}/main')
    //     ->httpMethod('GET')
    //     ->action([ '\Gzhegow\Router\Demo\Handler\Controller\DemoController', 'main' ])
    // ;
    // $router->addRoute($blueprint);

    // > добавляет middleware-посредник по пути (они отработают даже если маршрут не найден, но путь начинался с указанного)
    // > будьте внимательны, посредники отрабатывают в той последовательности, в которой заданы, если задать их до группы, то и отработают они раньше
    $router->middlewareOnPath('/api/v1/user', '\Gzhegow\Router\Demo\Handler\Middleware\DemoCorsMiddleware');
    $router->middlewareOnPath('/api/v1/user', '\Gzhegow\Router\Demo\Handler\Middleware\Demo1stMiddleware');
    $router->middlewareOnPath('/api/v1/user', '\Gzhegow\Router\Demo\Handler\Middleware\Demo2ndMiddleware');

    // // > можно привязывать посредники так же по тегу, теги в свою очередь привязывать к маршрутам
    // $router->middlewareOnTag('user', '\Gzhegow\Router\Demo\Handler\Middleware\DemoCorsMiddleware');

    // > добавляет fallback-обработчик по пути (если во время действия будет брошено исключение или роута не будет - запустится это действие)
    // > несколько fallback-обработчиков запустятся один за другим, пока какой-либо из них не вернет not-NULL результат, и если ни один - будет брошено \Gzhegow\Pipeline\PipelineException
    // > будьте внимательны, fallback-обработчики отрабатывают в той последовательности, в которой заданы, если задать их до группы, то и отработают они раньше
    $router->fallbackOnPath('/api/v1/user', '\Gzhegow\Router\Demo\Handler\Fallback\DemoThrowableFallback');

    // // > можно привязывать fallback-обработчики так же по тегу, теги в свою очередь привязывать к маршрутам
    // $router->fallbackOnTag('user', '\Gzhegow\Router\Demo\Handler\Fallback\DemoThrowableFallback');

    // > коммитим указанные выше роуты - это скомпилирует роуты (при компиляции роуты индексируются для быстрого поиска)
    $router->commit();
});


// > TEST
// > так можно искать маршруты с помощью имен или тегов
// > первый результат
// $route = $router->matchFirstByName('user.main');
// $route = $router->matchFirstByTag('user');
$fn = function () use ($router) {
    _print('TEST 1');
    echo PHP_EOL;

    $ids = [ 1, 2 ];
    $names = [ 'user.main' ];
    $tags = [ 'user' ];


    $routes = $router->matchAllByIds($ids);
    _print('[ RESULT ]', $routes);


    $batch = $router->matchAllByNames($names);
    foreach ( $batch as $i => $routes ) {
        _print('[ RESULT ]', $i, $routes);
    }

    $batch = $router->matchAllByTags($tags);
    foreach ( $batch as $i => $routes ) {
        _print('[ RESULT ]', $i, $routes);
    }


    $route = $router->matchFirstByIds($ids);
    _print('[ RESULT ]', $route);

    $route = $router->matchFirstByNames($names);
    _print('[ RESULT ]', $route);

    $route = $router->matchFirstByTags($tags);
    _print('[ RESULT ]', $route);
};
_assert_stdout($fn, [], '
"TEST 1"

"[ RESULT ]" | [ 1 => "{ object # Gzhegow\Router\Core\Route\Route }", 2 => "{ object # Gzhegow\Router\Core\Route\Route }" ]
"[ RESULT ]" | 0 | [ 1 => "{ object # Gzhegow\Router\Core\Route\Route }", 2 => "{ object # Gzhegow\Router\Core\Route\Route }", 3 => "{ object # Gzhegow\Router\Core\Route\Route }" ]
"[ RESULT ]" | 0 | [ 1 => "{ object # Gzhegow\Router\Core\Route\Route }", 2 => "{ object # Gzhegow\Router\Core\Route\Route }", 3 => "{ object # Gzhegow\Router\Core\Route\Route }", 4 => "{ object # Gzhegow\Router\Core\Route\Route }", 5 => "{ object # Gzhegow\Router\Core\Route\Route }" ]
"[ RESULT ]" | { object # Gzhegow\Router\Core\Route\Route }
"[ RESULT ]" | { object # Gzhegow\Router\Core\Route\Route }
"[ RESULT ]" | { object # Gzhegow\Router\Core\Route\Route }
');


// > TEST
// > так можно искать маршруты с помощью нескольких фильтров (если указать массивы - они работают как логическое ИЛИ, тогда как сами фильтры работают через логическое И
$fn = function () use ($router) {
    _print('TEST 2');
    echo PHP_EOL;

    $contract = \Gzhegow\Router\Core\Contract\RouterMatchContract::from([
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
        _print('[ RESULT ]', $id, $route);
    }
};
_assert_stdout($fn, [], '
"TEST 2"

"[ RESULT ]" | 1 | { object # Gzhegow\Router\Core\Route\Route }
"[ RESULT ]" | 2 | { object # Gzhegow\Router\Core\Route\Route }
');


// > TEST
// > так можно сгенерировать ссылки для зарегистрированных маршрутов
$fn = function () use ($router) {
    _print('TEST 3');
    echo PHP_EOL;

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
    _print('[ RESULT ]', $result);
};
_assert_stdout($fn, [], '
"TEST 3"

"[ RESULT ]" | [ "a" => "/api/v1/user/1/main", "b" => "/api/v1/user/2/main", "c" => "/api/v1/user/3/main" ]
');


// > TEST
// > так можно запустить выполнение маршрута в вашем файле index.php, на который указывает apache2/nginx
$fn = function () use ($router) {
    _print('TEST 4');
    echo PHP_EOL;

    $contract = \Gzhegow\Router\Core\Contract\RouterDispatchContract::from([ 'GET', '/api/v1/user/1/main' ]);

    $result = $router->dispatch($contract);
    _print('[ RESULT ]', $result);
};
_assert_stdout($fn, [], '
"TEST 4"

@before :: Gzhegow\Router\Demo\Handler\Middleware\DemoCorsMiddleware::__invoke
@before :: Gzhegow\Router\Demo\Handler\Middleware\Demo1stMiddleware::__invoke
@before :: Gzhegow\Router\Demo\Handler\Middleware\Demo2ndMiddleware::__invoke
Gzhegow\Router\Demo\Handler\Controller\DemoController::mainGet
@after :: Gzhegow\Router\Demo\Handler\Middleware\Demo2ndMiddleware::__invoke
@after :: Gzhegow\Router\Demo\Handler\Middleware\Demo1stMiddleware::__invoke
@after :: Gzhegow\Router\Demo\Handler\Middleware\DemoCorsMiddleware::__invoke
"[ RESULT ]" | 1
');


// > TEST
// > такого маршрута нет, запустится ранее указанный fallback-обработчик
$fn = function () use ($router) {
    _print('TEST 5');
    echo PHP_EOL;

    $contract = \Gzhegow\Router\Core\Contract\RouterDispatchContract::from([ 'GET', '/api/v1/user/not-found' ]);

    $result = $router->dispatch($contract);
    _print('[ RESULT ]', $result);
};
_assert_stdout($fn, [], '
"TEST 5"

@before :: Gzhegow\Router\Demo\Handler\Middleware\DemoCorsMiddleware::__invoke
@before :: Gzhegow\Router\Demo\Handler\Middleware\Demo1stMiddleware::__invoke
@before :: Gzhegow\Router\Demo\Handler\Middleware\Demo2ndMiddleware::__invoke
@after :: Gzhegow\Router\Demo\Handler\Middleware\Demo2ndMiddleware::__invoke
@after :: Gzhegow\Router\Demo\Handler\Middleware\Demo1stMiddleware::__invoke
@after :: Gzhegow\Router\Demo\Handler\Middleware\DemoCorsMiddleware::__invoke
Gzhegow\Router\Demo\Handler\Fallback\DemoThrowableFallback::__invoke
"[ RESULT ]" | "Gzhegow\Router\Demo\Handler\Fallback\DemoThrowableFallback::__invoke result."
');


// > TEST
// > такого маршрута нет, и одновременно с этим обработчик ошибок не был задан (либо был задан, но вернул NULL, что трактуется как "обработка не удалась")
$fn = function () use ($router) {
    _print('TEST 6');
    echo PHP_EOL;

    $contract = \Gzhegow\Router\Core\Contract\RouterDispatchContract::from([ 'GET', '/api/v1/not-found/not-found' ]);

    $result = null;
    try {
        $result = $router->dispatch($contract);
    }
    catch ( \Gzhegow\Router\Exception\Exception\DispatchException $e ) {
        _print('[ CATCH ]', get_class($e), $e->getMessage());

        foreach ( $e->getPreviousList() as $ee ) {
            _print('[ CATCH ]', get_class($ee), $ee->getMessage());
        }
    }
    _print('[ RESULT ]', $result);
};
_assert_stdout($fn, [], '
"TEST 6"

"[ CATCH ]" | "Gzhegow\Router\Exception\Exception\DispatchException" | "Unhandled exception occured during dispatch"
"[ CATCH ]" | "Gzhegow\Router\Exception\Runtime\NotFoundException" | "Route not found: `/api/v1/not-found/not-found` / `GET`"
"[ RESULT ]" | NULL
');


// > TEST
// > этот маршрут бросает \LogicException, запустятся DemoLogicExceptionFallback и DemoThrowableFallback
$fn = function () use ($router) {
    _print('TEST 7');
    echo PHP_EOL;

    $contract = \Gzhegow\Router\Core\Contract\RouterDispatchContract::from([ 'GET', '/api/v1/user/1/logic' ]);

    $result = $router->dispatch($contract);
    _print('[ RESULT ]', $result);
};
_assert_stdout($fn, [], '
"TEST 7"

@before :: Gzhegow\Router\Demo\Handler\Middleware\DemoCorsMiddleware::__invoke
@before :: Gzhegow\Router\Demo\Handler\Middleware\Demo1stMiddleware::__invoke
@before :: Gzhegow\Router\Demo\Handler\Middleware\Demo2ndMiddleware::__invoke
Gzhegow\Router\Demo\Handler\Controller\DemoController::logic
@after :: Gzhegow\Router\Demo\Handler\Middleware\Demo2ndMiddleware::__invoke
@after :: Gzhegow\Router\Demo\Handler\Middleware\Demo1stMiddleware::__invoke
@after :: Gzhegow\Router\Demo\Handler\Middleware\DemoCorsMiddleware::__invoke
Gzhegow\Router\Demo\Handler\Fallback\DemoRuntimeExceptionFallback::__invoke
Gzhegow\Router\Demo\Handler\Fallback\DemoLogicExceptionFallback::__invoke
"[ RESULT ]" | "Gzhegow\Router\Demo\Handler\Fallback\DemoLogicExceptionFallback::__invoke result."
');


// > TEST
// > этот маршрут бросает \RuntimeException, запустится DemoRuntimeExceptionFallback (т.к. он объявлен первым)
$fn = function () use ($router) {
    _print('TEST 8');
    echo PHP_EOL;

    $contract = \Gzhegow\Router\Core\Contract\RouterDispatchContract::from([ 'GET', '/api/v1/user/1/runtime' ]);

    $result = $router->dispatch($contract);
    _print('[ RESULT ]', $result);
};
_assert_stdout($fn, [], '
"TEST 8"

@before :: Gzhegow\Router\Demo\Handler\Middleware\DemoCorsMiddleware::__invoke
@before :: Gzhegow\Router\Demo\Handler\Middleware\Demo1stMiddleware::__invoke
@before :: Gzhegow\Router\Demo\Handler\Middleware\Demo2ndMiddleware::__invoke
Gzhegow\Router\Demo\Handler\Controller\DemoController::runtime
@after :: Gzhegow\Router\Demo\Handler\Middleware\Demo2ndMiddleware::__invoke
@after :: Gzhegow\Router\Demo\Handler\Middleware\Demo1stMiddleware::__invoke
@after :: Gzhegow\Router\Demo\Handler\Middleware\DemoCorsMiddleware::__invoke
Gzhegow\Router\Demo\Handler\Fallback\DemoRuntimeExceptionFallback::__invoke
"[ RESULT ]" | "Gzhegow\Router\Demo\Handler\Fallback\DemoRuntimeExceptionFallback::__invoke result."
');
