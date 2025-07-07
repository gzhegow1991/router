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
        echo $this->values(' | ', ...$values) . "\n";
    }


    function print_array($value, ?int $maxLevel = null, array $options = []) : void
    {
        echo $this->value_array($value, $maxLevel, $options) . "\n";
    }

    function print_array_multiline($value, ?int $maxLevel = null, array $options = []) : void
    {
        echo $this->value_array_multiline($value, $maxLevel, $options) . "\n";
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
        $config->dispatchTrailingSlashMode = \Gzhegow\Router\Router::TRAILING_SLASH_AS_IS;
        $config->dispatchIgnoreMethod = false;
        $config->dispatchForceMethod = null;

        // >>> кэш роутера
        $cacheDir = $ffn->root() . '/var/cache.test';
        $cacheNamespace = 'gzhegow.router';
        //
        $cacheDirpath = "{$cacheDir}/{$cacheNamespace}";
        $cacheFilename = "router.cache";
        $config->cache->cacheMode = \Gzhegow\Router\Core\Cache\RouterCache::CACHE_MODE_STORAGE;
        $config->cache->cacheDirpath = $cacheDirpath;
        $config->cache->cacheFilename = $cacheFilename;
        // //
        // $symfonyCacheMarshaller = new \Symfony\Component\Cache\Marshaller\DefaultMarshaller(null, true);
        // $symfonyCacheAdapter = new \Symfony\Component\Cache\Adapter\FilesystemAdapter(
        //     $cacheNamespace, $defaultLifetime = 0, $cacheDir,
        //     $symfonyCacheMarshaller
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
// $router->cacheClear();

// > вызываем функцию, которая загрузит кеш, и если его нет - выполнит регистрацию маршрутов и сохранение их в кэш (не обязательно)
$router->cacheRemember(
    static function (\Gzhegow\Router\RouterInterface $router) {
        // > добавляем паттерн, который можно использовать в маршрутах
        $router->pattern('{id}', '[0-9]+');
        $router->pattern('{lang}', '(en|ru)');


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
        $router->route('/{lang}', 'GET', [ '\Gzhegow\Router\Demo\Handler\Controller\DemoController', 'indexGet' ], 'index', '+lang');
        $router->route('/{lang}', 'POST', [ '\Gzhegow\Router\Demo\Handler\Controller\DemoController', 'indexPost' ], 'index', '+lang');

        // > будьте внимательны, указанные ниже маршруты - разные, отличаются закрывающимся slash, чтобы сделать их одним и тем же (ИЛИ):
        // - 1) уберите slash в конце
        // - 2) установите в конфиге параметр `dispatchTrailingSlashMode` на NEVER или ALWAYS
        // - 3) если хотите, чтобы роутер запрещал/требовал ставить slash в конце, поставьте параметр `compileTrailingSlashMode` на NEVER или ALWAYS
        $router->route('/hello-world', 'GET', [ '\Gzhegow\Router\Demo\Handler\Controller\DemoController', 'helloWorldGet' ], null, '-lang');
        $router->route('/hello-world/', 'POST', [ '\Gzhegow\Router\Demo\Handler\Controller\DemoController', 'helloWorldPost' ], null, '-lang');
        $router->route('/{lang}/hello-world', 'GET', [ '\Gzhegow\Router\Demo\Handler\Controller\DemoController', 'helloWorldGet' ], null, '+lang');
        $router->route('/{lang}/hello-world/', 'POST', [ '\Gzhegow\Router\Demo\Handler\Controller\DemoController', 'helloWorldPost' ], null, '+lang');

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
        $router->middlewareOnRoutePath('/api/v1', '\Gzhegow\Router\Demo\Handler\Middleware\DemoCorsMiddleware');
        $router->fallbackOnRoutePath('/api/v1', '\Gzhegow\Router\Demo\Handler\Fallback\DemoThrowableFallback');

        // // > для того, чтобы зарегистрировать маршруты удобно использовать группировку
        // $group = $router->group();

        // // > каждый множественный метод имеет аналог в единственном числе и аналог, который перезаписывает предыдущие установки
        // $group->setHttpMethods([]);
        // $group->httpMethods([]);
        // $group->httpMethod('');
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
                    //     //
                    //     // > также как и к группам, можно указывать теги и middleware-посредники/fallback-обработчики и для конкретного роута
                    //     // > при этом, если такие же уже были зарегистрированы раньше, то повторно они не добавятся
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
                    $group->route('/api/v1/user/{id}/main', 'GET', [ '\Gzhegow\Router\Demo\Handler\Controller\DemoController', 'apiV1UserMainGet' ], 'user.main');
                    $group->route('/api/v1/user/{id}/main', 'POST', [ '\Gzhegow\Router\Demo\Handler\Controller\DemoController', 'apiV1UserMainPost' ], 'user.main');

                    // > МОЖНО задавать группу в группе
                    // > НЕ РЕКОМЕНДУЕТСЯ РАЗБИВАТЬ ПУТЬ НА ЧАСТИ (КАК СДЕЛАНО НИЖЕ): путь к маршруту оставляют цельным, чтобы по нему можно было искать поиском в IDE
                    // > метод ->register() передаст все роуты в родительскую группу, соединив большинство параметров
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
$fn = function () use ($ffn, $router) {
    $ffn->print('TEST 1');
    echo "\n";


    $ids = [ 1, 2 ];

    $routes = $router->matchAllByIds($ids);
    $ffn->print_array_multiline($routes);
    echo "\n";


    $names = [ 'idx1' => 'user.main' ];
    $tags = [ 'idx1' => 'user' ];

    $batch = $router->matchAllByNames($names);
    foreach ( $batch as $i => $routes ) {
        $ffn->print('Attribute index', $i);
        $ffn->print_array_multiline($routes);
    }
    echo "\n";

    $batch = $router->matchAllByTags($tags);
    foreach ( $batch as $i => $routes ) {
        $ffn->print('Attribute index', $i);
        $ffn->print_array_multiline($routes);
    }
    echo "\n";


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

"Attribute index" | "idx1"
###
[
  9 => "{ object(serializable) # Gzhegow\Router\Core\Route\Route }",
  10 => "{ object(serializable) # Gzhegow\Router\Core\Route\Route }",
  11 => "{ object(serializable) # Gzhegow\Router\Core\Route\Route }"
]
###

"Attribute index" | "idx1"
###
[
  9 => "{ object(serializable) # Gzhegow\Router\Core\Route\Route }",
  10 => "{ object(serializable) # Gzhegow\Router\Core\Route\Route }",
  11 => "{ object(serializable) # Gzhegow\Router\Core\Route\Route }",
  12 => "{ object(serializable) # Gzhegow\Router\Core\Route\Route }",
  13 => "{ object(serializable) # Gzhegow\Router\Core\Route\Route }"
]
###

{ object(serializable) # Gzhegow\Router\Core\Route\Route }
{ object(serializable) # Gzhegow\Router\Core\Route\Route }
{ object(serializable) # Gzhegow\Router\Core\Route\Route }
');
$test->run();


// > TEST
// > так можно искать маршруты с по парам "имя-тег", чтобы найти два одинаковых роута для противоположных контекстов
$fn = function () use ($ffn, $router) {
    $ffn->print('TEST 2');
    echo "\n";


    $nameTagMethodList = [
        // key => [ name, tag, method, path ]
        '--' => [ null, null ],
        '-!' => [ null, false ],
        '!-' => [ false, null ],
        '!!' => [ false, false ],

        '-t' => [ null, '+lang' ],
        '!t' => [ false, '+lang' ],

        'n-' => [ 'index', null ],
        'n!' => [ 'index', false ],

        'nt' => [ 'index', '+lang' ],
    ];

    $batch = $router->matchAllByParams($nameTagMethodList);
    foreach ( $batch as $i => $routes ) {
        $ffn->print('Attribute index', $i);
        $ffn->print_array_multiline($routes);
        echo "\n";
    }
};
$test = $ffn->test($fn);
$test->expectStdout('
"TEST 2"

"Attribute index" | "--"
###
[
  1 => "{ object(serializable) # Gzhegow\Router\Core\Route\Route }",
  2 => "{ object(serializable) # Gzhegow\Router\Core\Route\Route }",
  3 => "{ object(serializable) # Gzhegow\Router\Core\Route\Route }",
  4 => "{ object(serializable) # Gzhegow\Router\Core\Route\Route }",
  5 => "{ object(serializable) # Gzhegow\Router\Core\Route\Route }",
  6 => "{ object(serializable) # Gzhegow\Router\Core\Route\Route }",
  7 => "{ object(serializable) # Gzhegow\Router\Core\Route\Route }",
  8 => "{ object(serializable) # Gzhegow\Router\Core\Route\Route }",
  9 => "{ object(serializable) # Gzhegow\Router\Core\Route\Route }",
  10 => "{ object(serializable) # Gzhegow\Router\Core\Route\Route }",
  11 => "{ object(serializable) # Gzhegow\Router\Core\Route\Route }",
  12 => "{ object(serializable) # Gzhegow\Router\Core\Route\Route }",
  13 => "{ object(serializable) # Gzhegow\Router\Core\Route\Route }"
]
###

"Attribute index" | "-!"
###
[
  1 => "{ object(serializable) # Gzhegow\Router\Core\Route\Route }",
  2 => "{ object(serializable) # Gzhegow\Router\Core\Route\Route }"
]
###

"Attribute index" | "!-"
###
[
  5 => "{ object(serializable) # Gzhegow\Router\Core\Route\Route }",
  6 => "{ object(serializable) # Gzhegow\Router\Core\Route\Route }",
  7 => "{ object(serializable) # Gzhegow\Router\Core\Route\Route }",
  8 => "{ object(serializable) # Gzhegow\Router\Core\Route\Route }"
]
###

"Attribute index" | "!!"
###
[]
###

"Attribute index" | "-t"
###
[
  3 => "{ object(serializable) # Gzhegow\Router\Core\Route\Route }",
  4 => "{ object(serializable) # Gzhegow\Router\Core\Route\Route }",
  7 => "{ object(serializable) # Gzhegow\Router\Core\Route\Route }",
  8 => "{ object(serializable) # Gzhegow\Router\Core\Route\Route }"
]
###

"Attribute index" | "!t"
###
[
  7 => "{ object(serializable) # Gzhegow\Router\Core\Route\Route }",
  8 => "{ object(serializable) # Gzhegow\Router\Core\Route\Route }"
]
###

"Attribute index" | "n-"
###
[
  1 => "{ object(serializable) # Gzhegow\Router\Core\Route\Route }",
  2 => "{ object(serializable) # Gzhegow\Router\Core\Route\Route }",
  3 => "{ object(serializable) # Gzhegow\Router\Core\Route\Route }",
  4 => "{ object(serializable) # Gzhegow\Router\Core\Route\Route }"
]
###

"Attribute index" | "n!"
###
[
  1 => "{ object(serializable) # Gzhegow\Router\Core\Route\Route }",
  2 => "{ object(serializable) # Gzhegow\Router\Core\Route\Route }"
]
###

"Attribute index" | "nt"
###
[
  3 => "{ object(serializable) # Gzhegow\Router\Core\Route\Route }",
  4 => "{ object(serializable) # Gzhegow\Router\Core\Route\Route }"
]
###
');
$test->run();


// > TEST
// > так можно сгенерировать ссылки для зарегистрированных маршрутов
$fn = function () use ($ffn, $router) {
    $ffn->print('TEST 4');
    echo "\n";


    $routeInstances = [];
    $routeInstances[ 'route1' ] = $router->matchFirstByNames([ 'user.main' ]);

    $routeNames = [];
    $routeNames[ 'route2' ] = 'user.main';
    $routeNames[ 'route3' ] = 'user.main';

    $routes = $routeInstances + $routeNames;

    $ids = [];
    $ids[ 'route1' ] = 1;
    $ids[ 'route2' ] = 2;
    $ids[ 'route3' ] = 3;

    $attributes = [];
    $attributes[ 'id' ] = $ids;

    // > можно передать либо список объектов (instance of Route::class) и/или список строк - имена роутов
    $result = $router->urls($routes, $attributes);
    $ffn->print_array_multiline($result);
};
$test = $ffn->test($fn);
$test->expectStdout('
"TEST 4"

###
[
  "route1" => "/api/v1/user/1/main",
  "route2" => "/api/v1/user/2/main",
  "route3" => "/api/v1/user/3/main"
]
###
');
$test->run();


// > TEST
// > так можно запустить выполнение маршрута в вашем файле index.php, на который указывает apache2/nginx
$fn = function () use ($ffn, $router) {
    $ffn->print('TEST 5');
    echo "\n";


    $contract = \Gzhegow\Router\Core\Dispatcher\Contract\RouterDispatcherRequestContract::fromArray(
        [ 'GET', '/api/v1/user/1/main' ]
    );

    $result = $router->dispatch($contract);
    echo "\n";
    $ffn->print('[ RESULT ]', $result);
};
$test = $ffn->test($fn);
$test->expectStdout('
"TEST 5"

@before :: Gzhegow\Router\Demo\Handler\Middleware\DemoCorsMiddleware::__invoke
@before :: Gzhegow\Router\Demo\Handler\Middleware\Demo1stMiddleware::__invoke
@before :: Gzhegow\Router\Demo\Handler\Middleware\Demo2ndMiddleware::__invoke
Gzhegow\Router\Demo\Handler\Controller\DemoController::apiV1UserMainGet
@after :: Gzhegow\Router\Demo\Handler\Middleware\Demo2ndMiddleware::__invoke
@after :: Gzhegow\Router\Demo\Handler\Middleware\Demo1stMiddleware::__invoke
@after :: Gzhegow\Router\Demo\Handler\Middleware\DemoCorsMiddleware::__invoke

"[ RESULT ]" | 1
');
$test->run();


// > TEST
// > такого маршрута нет, запустится ранее указанный fallback-обработчик
$fn = function () use ($ffn, $router) {
    $ffn->print('TEST 6');
    echo "\n";


    $result = $router->dispatch([ 'GET', '/api/v1/user/not-found' ]);
    echo "\n";
    $ffn->print('[ RESULT ]', $result);
};
$test = $ffn->test($fn);
$test->expectStdout('
"TEST 6"

@before :: Gzhegow\Router\Demo\Handler\Middleware\DemoCorsMiddleware::__invoke
@after :: Gzhegow\Router\Demo\Handler\Middleware\DemoCorsMiddleware::__invoke
Gzhegow\Router\Demo\Handler\Fallback\DemoThrowableFallback::__invoke

"[ RESULT ]" | "Gzhegow\Router\Demo\Handler\Fallback\DemoThrowableFallback::__invoke result."
');
$test->run();


// > TEST
// > такого маршрута нет, и одновременно с этим обработчик ошибок не был задан (либо был задан, но перебросил ошибку, что трактуется как "обработка не удалась")
$fn = function () use ($ffn, $router) {
    $ffn->print('TEST 7');
    echo "\n";


    $result = null;
    try {
        $result = $router->dispatch([ 'GET', '/not-found' ]);
    }
    catch ( \Gzhegow\Router\Exception\Exception\DispatchException $e ) {
        $lines = \Gzhegow\Lib\Lib::debugThrowabler()
            ->getPreviousMessagesAllLines($e, _DEBUG_THROWABLE_WITHOUT_FILE)
        ;

        echo '[ CATCH ]' . "\n";
        echo implode("\n", $lines) . "\n";
    }
    echo "\n";

    $ffn->print('[ RESULT ]', $result);
};
$test = $ffn->test($fn);
$test->expectStdout('
"TEST 7"

[ CATCH ]
[ 0 ] Unhandled exception during dispatch
{ object # Gzhegow\Router\Exception\Exception\DispatchException }
--
-- [ 0.0 ] Route not found: [ /not-found ][ GET ]
-- { object # Gzhegow\Router\Exception\Runtime\NotFoundException }

"[ RESULT ]" | NULL
');
$test->run();


// > TEST
// > этот маршрут бросает \LogicException, запустятся DemoLogicExceptionFallback и DemoThrowableFallback
$fn = function () use ($ffn, $router) {
    $ffn->print('TEST 8');
    echo "\n";


    $result = $router->dispatch([ 'GET', '/api/v1/user/1/logic' ]);
    echo "\n";
    $ffn->print('[ RESULT ]', $result);
};
$test = $ffn->test($fn);
$test->expectStdout('
"TEST 8"

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
    $ffn->print('TEST 9');
    echo "\n";


    $result = $router->dispatch([ 'GET', '/api/v1/user/1/runtime' ]);
    echo "\n";
    $ffn->print('[ RESULT ]', $result);
};
$test = $ffn->test($fn);
$test->expectStdout('
"TEST 9"

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

