# Router

Маршрутизатор с паттернами, построением URL, привязкой Middleware/Fallback как к самим маршрутам, так и к путям (если маршрут не найден).

Маршрутизация происходит через вложенное дерево маршрутов, а не через прямой обход всех маршрутов, то есть выполняется минимальное число регулярных выражений.

Поддерживает кеширование, можно использовать `symfony/cache` или сохранять в файл.

## Установить

```
composer require gzhegow/router
```

## Запустить тесты

```
php test.php
```

## Примеры и тесты

```php
<?php

require_once __DIR__ . '/../vendor/autoload.php';


// > настраиваем PHP
\Gzhegow\Lib\Lib::entrypoint()
    ->setDirRoot(__DIR__ . '/..')
    //
    ->useAll()
;


// > добавляем несколько функция для тестирования
$ffn = new class {
    function root() : string
    {
        return realpath(__DIR__ . '/..');
    }


    function value_array($value, ?int $maxLevel = null, array $options = []) : string
    {
        return \Gzhegow\Lib\Lib::debug()->value_array($value, $maxLevel, $options);
    }

    function value_array_multiline($value, ?int $maxLevel = null, array $options = []) : string
    {
        return \Gzhegow\Lib\Lib::debug()->value_array_multiline($value, $maxLevel, $options);
    }


    function values($separator = null, ...$values) : string
    {
        return \Gzhegow\Lib\Lib::debug()->values([], $separator, ...$values);
    }


    function print(...$values) : void
    {
        echo $this->values(' | ', ...$values) . PHP_EOL;
    }


    function print_array($value, ?int $maxLevel = null, array $options = []) : void
    {
        echo $this->value_array($value, $maxLevel, $options) . PHP_EOL;
    }

    function print_array_multiline($value, ?int $maxLevel = null, array $options = []) : void
    {
        echo $this->value_array_multiline($value, $maxLevel, $options) . PHP_EOL;
    }


    function test(\Closure $fn, array $args = []) : \Gzhegow\Lib\Modules\Test\Test
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);

        return \Gzhegow\Lib\Lib::test()->newTest()
            ->fn($fn, $args)
            ->trace($trace)
        ;
    }
};



// >>> ЗАПУСКАЕМ!

// > сначала всегда фабрика
$factory = new \Gzhegow\Router\RouterFactory();

// > создаем конфигурацию
$config = new \Gzhegow\Router\Core\Config\RouterConfig();
$config->configure(
    function (\Gzhegow\Router\Core\Config\RouterConfig $config) use ($ffn) {
        // >>> роутер
        $config->registerAllowObjectsAndClosures = false;
        $config->compileTrailingSlashMode = \Gzhegow\Router\Router::TRAILING_SLASH_AS_IS;
        $config->dispatchIgnoreMethod = false;
        $config->dispatchForceMethod = null;
        $config->dispatchTrailingSlashMode = \Gzhegow\Router\Router::TRAILING_SLASH_AS_IS;

        // >>> кэш роутера
        $config->cache->cacheMode = \Gzhegow\Router\Core\Cache\RouterCache::CACHE_MODE_STORAGE;
        //
        $cacheDir = $ffn->root() . '/var/cache';
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
    }
);

// > создаем кеш роутера
// > его задача сохранять маршруты в файл после того, как они будут скомпилированы и сохранены в виде дерева
$cache = new \Gzhegow\Router\Core\Cache\RouterCache($config->cache);

// > создаем диспетчер
// > его задача запускать действие, привязанное к маршруту, превращая его в конвеер, и формировать цепочку middleware
$dispatcher = new \Gzhegow\Router\Core\Dispatcher\RouterDispatcher();

// > создаем инвокер
// > его задача запускать PHP функции, которые составляют шаги конвеера
$invoker = new \Gzhegow\Router\Core\Invoker\RouterInvoker();

// > создаем поисковик
// > его задача искать в коллекциях маршрут по ID, имени, тегам или другим параметрам
$matcher = new \Gzhegow\Router\Core\Matcher\RouterMatcher();

// > создаем генератор ссылок
// > его задача создавать URL адреса на базе имеющихся в коллекции маршрутов
$urlGenerator = new Gzhegow\Router\Core\UrlGenerator\RouterUrlGenerator();

// > создаем роутер
$router = new \Gzhegow\Router\RouterFacade(
    $factory,
    //
    $cache,
    $dispatcher,
    $invoker,
    $matcher,
    $urlGenerator,
    //
    $config
);

// > создаем фасад, если удобно пользоваться статикой
\Gzhegow\Router\Router::setFacade($router);


// // > так можно очистить кэш
$router->cacheClear();

// > вызываем функцию, которая загрузит кеш, и если его нет - выполнит регистрацию маршрутов и сохранение их в кэш (не обязательно)
$router->cacheRemember(
    static function (\Gzhegow\Router\RouterInterface $router) {
        // > добавляем паттерн, который можно использовать в маршрутах
        $router->pattern('{id}', '[0-9]+');


        // // > так можно добавлять маршруты в главную группу
        // $router->route('/', 'GET', [ '\Gzhegow\Router\Demo\Handler\Controller\DemoController', 'indexGet' ], 'user.main');
        //
        // // > можно добавлять роуты на основе "чертежа" (это неявно используется во всех остальных способах)
        // $blueprint = $router->blueprint()
        //     ->path('/')
        //     ->httpMethod('GET')
        //     ->action([ '\Gzhegow\Router\Demo\Handler\Controller\DemoController', 'indexGet' ])
        //     ->name('user.main')
        // ;
        // $router->addRoute($blueprint);
        //
        $router->route('/', 'GET', [ '\Gzhegow\Router\Demo\Handler\Controller\DemoController', 'indexGet' ], 'index');
        $router->route('/', 'POST', [ '\Gzhegow\Router\Demo\Handler\Controller\DemoController', 'indexPost' ], 'index');


        // // > добавляет middleware-посредник по пути (они отработают даже если маршрут не найден, но путь начинался с указанного)
        // // > будьте внимательны, посредники отрабатывают в той последовательности, в которой заданы, если задать их до группы, то и отработают они раньше
        // $router->middlewareOnPath('/', '\Gzhegow\Router\Demo\Handler\Middleware\Demo1stMiddleware');
        // $router->middlewareOnPath('/', '\Gzhegow\Router\Demo\Handler\Middleware\Demo2ndMiddleware');
        //
        // // > можно привязывать посредники так же по тегу, теги в свою очередь привязывать к маршрутам или группам
        // $router->middlewareOnTag('tag1', '\Gzhegow\Router\Demo\Handler\Middleware\Demo1stMiddleware');
        // $router->middlewareOnTag('tag1', '\Gzhegow\Router\Demo\Handler\Middleware\Demo2ndMiddleware');
        //
        // // > добавляет fallback-обработчик по пути (если во время действия будет брошено исключение или роута не будет - запустится это действие)
        // // > несколько fallback-обработчиков запустятся один за другим, пока какой-либо из них не вернет not-NULL результат, и если ни один - будет брошено \Gzhegow\Pipeline\PipelineException
        // // > будьте внимательны, fallback-обработчики отрабатывают в той последовательности, в которой заданы, если задать их до группы, то и отработают они раньше
        // $router->fallbackOnPath('/', '\Gzhegow\Router\Demo\Handler\Fallback\DemoThrowableFallback');
        //
        // // > можно привязывать fallback-обработчики так же по тегу, теги в свою очередь привязывать к маршрутам или группам
        // $router->fallbackOnTag('tag1', '\Gzhegow\Router\Demo\Handler\Fallback\DemoThrowableFallback');
        //
        $router->middlewareOnPath('/api/v1/user', '\Gzhegow\Router\Demo\Handler\Middleware\DemoCorsMiddleware');
        $router->middlewareOnPath('/api/v1/user', '\Gzhegow\Router\Demo\Handler\Middleware\Demo1stMiddleware');
        $router->middlewareOnPath('/api/v1/user', '\Gzhegow\Router\Demo\Handler\Middleware\Demo2ndMiddleware');
        //
        $router->fallbackOnPath('/api/v1/user', '\Gzhegow\Router\Demo\Handler\Fallback\DemoThrowableFallback');

        // // > для того, чтобы зарегистрировать маршруты удобно использовать группировку
        // $group = $router->group();

        // // > каждый множественный метод имеет аналог в единственном числе и аналог, который перезаписывает предыдущие установки
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
            ->tags([
                'api',
                'api.v1',
                'user',
            ])
            //
            // > добавляем middleware-посредники для каждого роута в группе
            // > будьте внимательны, посредники добавляются К РОУТАМ, а не к ИХ ПУТЯМ, то есть "если роут найден - посредники будут запущены"
            ->middlewares([
                '\Gzhegow\Router\Demo\Handler\Middleware\DemoCorsMiddleware',
                '\Gzhegow\Router\Demo\Handler\Middleware\Demo1stMiddleware',
                '\Gzhegow\Router\Demo\Handler\Middleware\Demo2ndMiddleware',
            ])
            //
            // > добавляем fallback-обработчики для каждого роута в группе
            // > будьте внимательны, обработчики добавляются К РОУТАМ, а не к ИХ ПУТЯМ, то есть "если роут найден и бросил ошибку - обработчики будут запущены"
            ->fallbacks([
                '\Gzhegow\Router\Demo\Handler\Fallback\DemoRuntimeExceptionFallback',
                '\Gzhegow\Router\Demo\Handler\Fallback\DemoLogicExceptionFallback',
                '\Gzhegow\Router\Demo\Handler\Fallback\DemoThrowableFallback',
            ])
            //
            // регистрируем группу
            ->register(
                static function (\Gzhegow\Router\Core\Route\RouteGroup $group) {
                    // // > пример регистрации маршрута
                    // $route = $group
                    //     ->route(
                    //         $path = '/api/v1/user/{id}/main',
                    //         $httpMethods = [ 'GET' ],
                    //         $action = '\Gzhegow\Router\Demo\Handler\Action\DemoCorsAction',
                    //         $name = '',
                    //         $tags = []
                    //     )
                    //     ->name('')
                    //     ->tags([])
                    // ;

                    // // > можно указывать теги и middleware-посредники/fallback-обработчики и для конкретного роута
                    // // > при этом, если такие же уже были зарегистрированы раньше, то повторно они не добавятся
                    // $route
                    //     ->middlewares([])
                    //     ->fallbacks([])
                    // ;

                    // > подключаем CORS для API
                    // > в поставляемом действии режим "разрешить всё", если нужны тонкие настройки - стоит наследовать класс или написать свой
                    $group->route('/api/v1/user/{id}/main', 'OPTIONS', '\Gzhegow\Router\Demo\Handler\Action\DemoCorsAction', 'user.main');
                    // > подключаем поставляемый middleware-посредник для CORS-заголовков
                    // > в поставляемом посреднике режим "разрешить всё", если нужны тонкие настройки - стоит наследовать класс или написать свой
                    $group->middleware('\Gzhegow\Router\Demo\Handler\Middleware\DemoCorsMiddleware');

                    // > добавляем маршруты
                    $group->route('/api/v1/user/{id}/main', 'GET', [ '\Gzhegow\Router\Demo\Handler\Controller\DemoController', 'mainGet' ], 'user.main');
                    $group->route('/api/v1/user/{id}/main', 'POST', [ '\Gzhegow\Router\Demo\Handler\Controller\DemoController', 'mainPost' ], 'user.main');

                    // > можно задавать группу в группе
                    // > метод ->register() передаст все роуты в родительскую группу
                    // > НЕ РЕКОМЕНДУЕТСЯ - путь к маршруту надо оставлять цельным, чтобы по нему можно было искать поиском в IDE
                    $group->group()
                        ->name('user')
                        ->path('/api/v1/user/{id}')
                        ->register(static function (\Gzhegow\Router\Core\Route\RouteGroup $group) {
                            $group->route('/logic', 'GET', [ '\Gzhegow\Router\Demo\Handler\Controller\DemoController', 'errorLogic' ], 'logic');
                            $group->route('/runtime', 'GET', [ '\Gzhegow\Router\Demo\Handler\Controller\DemoController', 'errorRuntime' ], 'runtime');
                        })
                    ;
                }
            )
        ;

        // > коммитим указанные выше роуты - это скомпилирует роуты (при компиляции роуты индексируются для быстрого поиска)
        $router->commit();
    }
);



// >>> ТЕСТЫ

// > TEST
// > так можно искать маршруты с помощью имен или тегов
// > первый результат
// $route = $router->matchFirstByName('user.main');
// $route = $router->matchFirstByTag('user');
$fn = function () use ($ffn, $router) {
    $ffn->print('TEST 1');
    echo PHP_EOL;


    $ids = [ 1, 2 ];
    $names = [ 0 => 'user.main' ];
    $tags = [ 0 => 'user' ];


    $routes = $router->matchAllByIds($ids);
    $ffn->print_array_multiline($routes);
    echo PHP_EOL;


    $batch = $router->matchAllByNames($names);
    foreach ( $batch as $i => $routes ) {
        $ffn->print($i);
        $ffn->print_array_multiline($routes);
    }
    echo PHP_EOL;

    $batch = $router->matchAllByTags($tags);
    foreach ( $batch as $i => $routes ) {
        $ffn->print($i);
        $ffn->print_array_multiline($routes);
    }
    echo PHP_EOL;


    $route = $router->matchFirstByIds($ids);
    $ffn->print($route);

    $route = $router->matchFirstByNames($names);
    $ffn->print($route);

    $route = $router->matchFirstByTags($tags);
    $ffn->print($route);
};
$test = $ffn->test($fn);
$test->expectStdout('
"TEST 1"

###
[
  1 => "{ object(serializable) # Gzhegow\Router\Core\Route\Route }",
  2 => "{ object(serializable) # Gzhegow\Router\Core\Route\Route }"
]
###

0
###
[
  3 => "{ object(serializable) # Gzhegow\Router\Core\Route\Route }",
  4 => "{ object(serializable) # Gzhegow\Router\Core\Route\Route }",
  5 => "{ object(serializable) # Gzhegow\Router\Core\Route\Route }"
]
###

0
###
[
  3 => "{ object(serializable) # Gzhegow\Router\Core\Route\Route }",
  4 => "{ object(serializable) # Gzhegow\Router\Core\Route\Route }",
  5 => "{ object(serializable) # Gzhegow\Router\Core\Route\Route }",
  6 => "{ object(serializable) # Gzhegow\Router\Core\Route\Route }",
  7 => "{ object(serializable) # Gzhegow\Router\Core\Route\Route }"
]
###

{ object(serializable) # Gzhegow\Router\Core\Route\Route }
{ object(serializable) # Gzhegow\Router\Core\Route\Route }
{ object(serializable) # Gzhegow\Router\Core\Route\Route }
');
$test->run();


// > TEST
// > так можно искать маршруты с помощью нескольких фильтров (если указать массивы - они работают как логическое ИЛИ, тогда как сами фильтры работают через логическое И
$fn = function () use ($ffn, $router) {
    $ffn->print('TEST 2');
    echo PHP_EOL;


    $contract = \Gzhegow\Router\Core\Matcher\RouterMatcherContract::from([
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
        $ffn->print($id, $route);
    }
};
$test = $ffn->test($fn);
$test->expectStdout('
"TEST 2"

4 | { object(serializable) # Gzhegow\Router\Core\Route\Route }
5 | { object(serializable) # Gzhegow\Router\Core\Route\Route }
');
$test->run();


// > TEST
// > так можно сгенерировать ссылки для зарегистрированных маршрутов
$fn = function () use ($ffn, $router) {
    $ffn->print('TEST 3');
    echo PHP_EOL;


    $instances = [];
    $instances[ 'a' ] = $router->matchFirstByNames('user.main');

    $names = [];
    $names[ 'b' ] = 'user.main';
    $names[ 'c' ] = 'user.main';

    $routes = $instances + $names;

    $ids = [];
    $ids[ 'a' ] = 1;
    $ids[ 'b' ] = 2;
    $ids[ 'c' ] = 3;

    $attributes = [];
    $attributes[ 'id' ] = $ids;

    // > можно передать либо список объектов (instance of Route::class) и/или список строк (route `name`)
    $result = $router->urls($routes, $attributes);
    $ffn->print_array_multiline($result);
};
$test = $ffn->test($fn);
$test->expectStdout('
"TEST 3"

###
[
  "a" => "/api/v1/user/1/main",
  "b" => "/api/v1/user/2/main",
  "c" => "/api/v1/user/3/main"
]
###
');
$test->run();


// > TEST
// > так можно запустить выполнение маршрута в вашем файле index.php, на который указывает apache2/nginx
$fn = function () use ($ffn, $router) {
    $ffn->print('TEST 4');
    echo PHP_EOL;


    $contract = \Gzhegow\Router\Core\Dispatcher\RouterDispatcherContract::fromArray(
        [ 'GET', '/api/v1/user/1/main' ]
    );

    $result = $router->dispatch($contract);
    echo PHP_EOL;

    $ffn->print('[ RESULT ]', $result);
};
$test = $ffn->test($fn);
$test->expectStdout('
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
$test->run();


// > TEST
// > такого маршрута нет, запустится ранее указанный fallback-обработчик
$fn = function () use ($ffn, $router) {
    $ffn->print('TEST 5');
    echo PHP_EOL;


    $contract = \Gzhegow\Router\Core\Dispatcher\RouterDispatcherContract::fromArray(
        [ 'GET', '/api/v1/user/not-found' ]
    );

    $result = $router->dispatch($contract);
    echo PHP_EOL;

    $ffn->print('[ RESULT ]', $result);
};
$test = $ffn->test($fn);
$test->expectStdout('
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
$test->run();


// > TEST
// > такого маршрута нет, и одновременно с этим обработчик ошибок не был задан (либо был задан, но вернул NULL, что трактуется как "обработка не удалась")
$fn = function () use ($ffn, $router) {
    $ffn->print('TEST 6');
    echo PHP_EOL;


    $contract = \Gzhegow\Router\Core\Dispatcher\RouterDispatcherContract::fromArray(
        [ 'GET', '/api/v1/not-found/not-found' ]
    );

    $result = null;
    try {
        $result = $router->dispatch($contract);
    }
    catch ( \Gzhegow\Router\Exception\Exception\DispatchException $e ) {
        $lines = \Gzhegow\Lib\Lib::debugThrowabler()
            ->getPreviousMessagesLines($e, _DEBUG_THROWABLE_WITHOUT_FILE)
        ;

        echo '[ CATCH ]' . PHP_EOL;
        echo implode(PHP_EOL, $lines) . PHP_EOL;
    }
    echo PHP_EOL;

    $ffn->print('[ RESULT ]', $result);
};
$test = $ffn->test($fn);
$test->expectStdout('
"TEST 6"

[ CATCH ]
[ 0 ] Unhandled exception occured during dispatch
{ object # Gzhegow\Router\Exception\Exception\DispatchException }
--
-- [ 0.0 ] Unhandled exception during processing pipeline
-- { object # Gzhegow\Lib\Exception\Runtime\PipeException }
----
---- [ 0.0.0 ] Route not found: [ /api/v1/not-found/not-found ][ GET ]
---- { object # Gzhegow\Router\Exception\Runtime\NotFoundException }

"[ RESULT ]" | NULL
');
$test->run();


// > TEST
// > этот маршрут бросает \LogicException, запустятся DemoLogicExceptionFallback и DemoThrowableFallback
$fn = function () use ($ffn, $router) {
    $ffn->print('TEST 7');
    echo PHP_EOL;


    $contract = \Gzhegow\Router\Core\Dispatcher\RouterDispatcherContract::fromArray(
        [ 'GET', '/api/v1/user/1/logic' ]
    );

    $result = $router->dispatch($contract);
    echo PHP_EOL;

    $ffn->print('[ RESULT ]', $result);
};
$test = $ffn->test($fn);
$test->expectStdout('
"TEST 7"

@before :: Gzhegow\Router\Demo\Handler\Middleware\DemoCorsMiddleware::__invoke
@before :: Gzhegow\Router\Demo\Handler\Middleware\Demo1stMiddleware::__invoke
@before :: Gzhegow\Router\Demo\Handler\Middleware\Demo2ndMiddleware::__invoke
Gzhegow\Router\Demo\Handler\Controller\DemoController::errorLogic
@after :: Gzhegow\Router\Demo\Handler\Middleware\Demo2ndMiddleware::__invoke
@after :: Gzhegow\Router\Demo\Handler\Middleware\Demo1stMiddleware::__invoke
@after :: Gzhegow\Router\Demo\Handler\Middleware\DemoCorsMiddleware::__invoke
Gzhegow\Router\Demo\Handler\Fallback\DemoRuntimeExceptionFallback::__invoke
Gzhegow\Router\Demo\Handler\Fallback\DemoLogicExceptionFallback::__invoke

"[ RESULT ]" | "Gzhegow\Router\Demo\Handler\Fallback\DemoLogicExceptionFallback::__invoke result."
');
$test->run();


// > TEST
// > этот маршрут бросает \RuntimeException, запустится DemoRuntimeExceptionFallback (т.к. он объявлен первым)
$fn = function () use ($ffn, $router) {
    $ffn->print('TEST 8');
    echo PHP_EOL;


    $contract = \Gzhegow\Router\Core\Dispatcher\RouterDispatcherContract::fromArray(
        [ 'GET', '/api/v1/user/1/runtime' ]
    );

    $result = $router->dispatch($contract);
    echo PHP_EOL;

    $ffn->print('[ RESULT ]', $result);
};
$test = $ffn->test($fn);
$test->expectStdout('
"TEST 8"

@before :: Gzhegow\Router\Demo\Handler\Middleware\DemoCorsMiddleware::__invoke
@before :: Gzhegow\Router\Demo\Handler\Middleware\Demo1stMiddleware::__invoke
@before :: Gzhegow\Router\Demo\Handler\Middleware\Demo2ndMiddleware::__invoke
Gzhegow\Router\Demo\Handler\Controller\DemoController::errorRuntime
@after :: Gzhegow\Router\Demo\Handler\Middleware\Demo2ndMiddleware::__invoke
@after :: Gzhegow\Router\Demo\Handler\Middleware\Demo1stMiddleware::__invoke
@after :: Gzhegow\Router\Demo\Handler\Middleware\DemoCorsMiddleware::__invoke
Gzhegow\Router\Demo\Handler\Fallback\DemoRuntimeExceptionFallback::__invoke

"[ RESULT ]" | "Gzhegow\Router\Demo\Handler\Fallback\DemoRuntimeExceptionFallback::__invoke result."
');
$test->run();
```

