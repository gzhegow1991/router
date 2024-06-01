# Router

Маршрутизатор с паттернами, построением URL, привязкой Middleware/Fallback как к самим маршрутам, так и к путям (если маршрут не найден).

Маршрутизация происходит через вложенное дерево маршрутов, а не через прямой обход всех маршрутов, то есть выполняется минимальное число регулярных выражений.

Поддерживает кеширование, можно использовать `symfony/cache` или сохранять в файл.

## Установка

```
composer require gzhegow/router;
```

## Пример

```php
<?php

use Gzhegow\Router\Lib;
use Gzhegow\Router\Router;
use Gzhegow\Router\RouterFactory;
use Gzhegow\Router\RouterInterface;
use Gzhegow\Router\Cache\RouterCache;
use Gzhegow\Router\Contract\RouterMatchContract;
use Gzhegow\Router\Contract\RouterDispatchContract;
use Gzhegow\Router\Handler\Middleware\CorsMiddleware;
use Gzhegow\Router\Handler\Demo\Fallback\DemoFallback;
use Gzhegow\Router\Handler\Demo\Controller\DemoController;
use Gzhegow\Router\Handler\Demo\Fallback\DemoLogicFallback;
use Gzhegow\Router\Handler\Demo\Middleware\Demo1stMiddleware;
use Gzhegow\Router\Handler\Demo\Middleware\Demo2ndMiddleware;
use Gzhegow\Router\Handler\Demo\Fallback\DemoRuntimeFallback;


require_once __DIR__ . '/vendor/autoload.php';


// > настраиваем PHP
ini_set('memory_limit', '32M');

// > настраиваем обработку ошибок
error_reporting(E_ALL & ~E_USER_NOTICE);
set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    if (error_reporting() & $errno) {
        throw new \ErrorException($errstr, -1, $errno, $errfile, $errline);
    }
});
set_exception_handler(function ($e) {
    var_dump($e);
    die();
});


// > создаем роутер
$router = (new RouterFactory())->newRouter();

// >>> Ставим настройки роутера
$settings = [
    // > позволить регистрировать callable с объектами и \Closure, кеш в этом случае работать не будет
    $registerAllowObjectsAndClosures = false,
    //
    // > на этапе добавления проверить маршрут на предмет того, заканчивается ли он на слеш и бросить исключение
    $compileTrailingSlashMode = Router::TRAILING_SLASH_AS_IS,
    // Router::TRAILING_SLASH_AS_IS // > оставить как есть
    // Router::TRAILING_SLASH_ALWAYS, // > при вызове маршрута добавлять к нему trailing-slash
    // Router::TRAILING_SLASH_NEVER, // > при вызове маршрута удалить trailing-slash
    //
    // > не учитывать метод при вызове dispatch(), в этом случае POST действия будут отрабатывать даже на запросы из браузера (однако порядок регистрации важен, если на том же маршруте GET/POST, то отработает тот что раньше зарегистрирован)
    $dispatchIgnoreMethod = false,
    //
    // > подменить метод при вызове dispatch(), например, несмотря на GET запрос выполнить действие, зарегистрированное на POST
    $dispatchForceMethod = null, // HttpMethod::METHOD_POST | HttpMethod::METHOD_GET | HttpMethod::METHOD_PUT | HttpMethod::METHOD_OPTIONS | etc.
    //
    // > при вызове dispatch() к переданному маршруту добавить обратный слеш, удалить его или оставить без изменений
    $dispatchTrailingSlashMode = Router::TRAILING_SLASH_AS_IS,  // TRAILING_SLASH_ALWAYS | TRAILING_SLASH_NEVER
    // Router::TRAILING_SLASH_AS_IS // > оставить как есть
    // Router::TRAILING_SLASH_ALWAYS, // > при вызове маршрута добавлять к нему trailing-slash
    // Router::TRAILING_SLASH_NEVER, // > при вызове маршрута удалить trailing-slash
];
$router->setSettings(...$settings);

// >>> Настраиваем кеш для роутера
$cacheDir = __DIR__ . '/var/cache';
$cacheNamespace = 'app.router';

// >>> Можно использовать путь к файлу, в этом случае кеш будет сделан через file_{get|put}_contents() + (un)serialize()
$cacheDirpath = "{$cacheDir}/{$cacheNamespace}";
$cacheFilename = "router.cache";
$router->setCacheSettings([
    // 'cacheMode'     => Reflector::CACHE_MODE_NO_CACHE, // > не использовать кеш совсем
    'cacheMode'     => RouterCache::CACHE_MODE_STORAGE, // > использовать файловую систему или адаптер (хранилище)
    //
    'cacheDirpath'  => $cacheDirpath,
    'cacheFilename' => $cacheFilename,
]);

// >>> Либо можно установить пакет `composer require symfony/cache` и использовать адаптер, чтобы запихивать в Редис например
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

// > вызываем функцию, которая загрузит кеш, и если его нет - выполнит регистрацию маршрутов
$router->cacheRemember(function (RouterInterface $router) {
    // > добавляем паттерн, который можно использовать в маршрутах
    $router->pattern('{id}', '[0-9]+');

    // > добавляет Middleware по пути (они отработают даже если маршрут не найден, но путь начинался с указанного)
    $router->middlewareOnPath('/api/v1/user', CorsMiddleware::class);
    $router->middlewareOnPath('/api/v1/user', Demo1stMiddleware::class);
    $router->middlewareOnPath('/api/v1/user', Demo2ndMiddleware::class);

    // > добавляет Fallback по пути (если во время действия будет брошено исключение или роута не будет - запустится это действие)
    // > несколько Fallback запустятся один за другим, пока какой-либо из них не вернет результат, если результата так и не будет - исключение будет брошено снова
    $router->fallbackOnPath('/api/v1/user', DemoFallback::class);

    // > к маршрутам можно привязывать теги, на теги подключать Middleware и Fallback, также по тегам можно искать маршруты
    // $router->middlewareOnTag('user', DemoMiddleware::class);
    // $router->fallbackOnTag('user', DemoFallback::class);

    // > для того, чтобы зарегистрировать маршруты удобно использовать группировку
    // $router->group()->middlewareList([]); // ->middlewareList([]) // > использование метода, который заканчивается на `List` перезапишет предыдущие
    // $router->group()->tagList([]); // ->tagList([]) // > использование метода, который заканчивается на `List` перезапишет предыдущие
    $router->group()
        ->tag('user') // > ставим тег для каждого роута в группе
        ->middleware([ CorsMiddleware::class ]) // > подключаем CORS (в примере сделано "разрешить всё", если нужны тонкие настройки - наследуйте класс `CorsMiddleware` или напишите новый)
        ->middleware([
            // > подключаем другие Middleware
            Demo1stMiddleware::class,
            Demo2ndMiddleware::class,
        ])
        ->fallback([
            DemoFallback::class, // > этот Fallback ранее уже был зарегистрирован по пути, на этапе вызова они совпадут и вызовется один раз
            DemoLogicFallback::class, // > этот Fallback написан обрабатывать только \LogicException
            DemoRuntimeFallback::class, // > этот Fallback написан обрабатывать только \RuntimeException
        ])
        ->register(function (RouterInterface $router) {
            $router->route('/api/v1/user/{id}/main', 'GET', [ DemoController::class, 'mainGet' ], 'user.main');
            $router->route('/api/v1/user/{id}/main', 'POST', [ DemoController::class, 'mainPost' ], 'user.main'); // > это имя мы уже использовали выше, однако path совпадает и так можно

            // > В принципе, обработку Cors можно подключить и через CorsAction, но без Middleware всё равно не обойдется, т.к. заголовки отправляются и в каждом запросе и в методе OPTIONS - но там их больше
            // $router->route('/api/v1/user/{id}/main', 'OPTIONS', CorsAction::class, 'user.main');

            $router->route('/api/v1/user/{id}/logic', 'GET', $action = [ DemoController::class, 'logic' ], $name = 'user.logic');
            $router->route('/api/v1/user/{id}/runtime', 'GET', $action = [ DemoController::class, 'runtime' ])
                ->middleware([
                    // > эти Middleware уже были заданы на группе, на этапе вызова они совпадут и вызовутся один раз
                    CorsMiddleware::class,
                    Demo1stMiddleware::class,
                    Demo2ndMiddleware::class,
                ])
                ->fallback([
                    // > эти Fallback уже были заданы на группе, на этапе вызова они совпадут и вызовутся один раз
                    DemoFallback::class,
                    DemoLogicFallback::class,
                    DemoRuntimeFallback::class,
                ])
            ;
        })
    ;

    // > однако так тоже можно
    // $router->routeAdd(
    //     $router->blueprint()
    //         ->path('/api/v1/user/{id}/main')
    //         ->httpMethod('GET')
    //         ->action([ DemoController::class, 'main' ])
    // );
});

// > так можно искать маршруты с помощью имен или тегов
echo 'Case 1:' . PHP_EOL;
// $batch = $router->matchAllByNames([ 'user.main' ]); // > все результаты
// $batch = $router->matchAllByTags([ 'user' ]); // > все результаты
// $routes = $router->matchFirstByName('user.main'); // > первый результат
// $routes = $router->matchFirstByTag('user'); // > первый результат
$batch = $router->matchAllByNames([ 'user.main' ]);
foreach ( $batch as $id => $routes ) {
    var_dump([
        $id,
        array_map([ Lib::class, 'php_dump' ], $routes),
    ]);
}
// array(2) {
//   [0]=>
//   int(0)
//   [1]=>
//   array(2) {
//     [0]=>
//     string(43) "{ object(Gzhegow\Router\Route\Route # 51) }"
//     [1]=>
//     string(43) "{ object(Gzhegow\Router\Route\Route # 56) }"
//   }
// }
echo PHP_EOL;

// > так можно искать маршруты с помощью нескольких фильтров (если указать массивы - они работают как логическое ИЛИ, тогда как сами фильтры работают через логическое И
echo 'Case 2:' . PHP_EOL;
$contract = RouterMatchContract::from([
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
    var_dump([ $id, Lib::php_dump($route) ]);
}
// array(2) {
//   [0]=>
//   int(0)
//   [1]=>
//   string(43) "{ object(Gzhegow\Router\Route\Route # 51) }"
// }
// array(2) {
//   [0]=>
//   int(1)
//   [1]=>
//   string(43) "{ object(Gzhegow\Router\Route\Route # 56) }"
// }
echo PHP_EOL;

// > так можно запустить выполнение маршрута в вашем файле index.php, на который указывает apache2/nginx
echo 'Case 3:' . PHP_EOL;
$contract = RouterDispatchContract::from([ 'GET', '/api/v1/user/1/main' ]);
$result = $router->dispatch($contract);
var_dump($result);
// @before :: Gzhegow\Router\Handler\Demo\Middleware\Demo1stMiddleware::__invoke
// @before :: Gzhegow\Router\Handler\Demo\Middleware\Demo2ndMiddleware::__invoke
// string(59) "Gzhegow\Router\Handler\Demo\Controller\DemoController::main"
// @after :: Gzhegow\Router\Handler\Demo\Middleware\Demo2ndMiddleware::__invoke
// @after :: Gzhegow\Router\Handler\Demo\Middleware\Demo1stMiddleware::__invoke
// int(1)
echo PHP_EOL;

// > вот такого маршрута нет, запустится ранее указанный fallback, однако Fallback возвращает NULL, поэтому исключение все равно будет выброшено ещё раз
echo 'Case 4:' . PHP_EOL;
$contract = RouterDispatchContract::from([ 'GET', '/api/v1/user/not-found' ]);
$result = null;
try {
    $result = $router->dispatch($contract);
}
catch ( \Throwable $e ) {
    var_dump('CATCH: ' . get_class($e));
}
var_dump($result);
// string(59) "Gzhegow\Router\Handler\Demo\Fallback\DemoFallback::__invoke"
// string(57) "CATCH: Gzhegow\Router\Exception\Runtime\NotFoundException"
// NULL
echo PHP_EOL;

// > этот маршрут бросает \LogicException, запустятся DemoFallback и DemoLogicFallback
echo 'Case 5:' . PHP_EOL;
$contract = RouterDispatchContract::from([ 'GET', '/api/v1/user/1/logic' ]);
$result = $router->dispatch($contract);
var_dump($result);
// @before :: Gzhegow\Router\Handler\Demo\Middleware\Demo1stMiddleware::__invoke
// @before :: Gzhegow\Router\Handler\Demo\Middleware\Demo2ndMiddleware::__invoke
// string(60) "Gzhegow\Router\Handler\Demo\Controller\DemoController::logic"
// string(59) "Gzhegow\Router\Handler\Demo\Fallback\DemoFallback::__invoke"
// string(64) "Gzhegow\Router\Handler\Demo\Fallback\DemoLogicFallback::__invoke"
// @after :: Gzhegow\Router\Handler\Demo\Middleware\Demo2ndMiddleware::__invoke
// @after :: Gzhegow\Router\Handler\Demo\Middleware\Demo1stMiddleware::__invoke
// bool(true)
echo PHP_EOL;

// > этот маршрут бросает \LogicException, запустятся DemoFallback и DemoRuntimeFallback
echo 'Case 6:' . PHP_EOL;
$contract = RouterDispatchContract::from([ 'GET', '/api/v1/user/1/runtime' ]);
$result = $router->dispatch($contract);
var_dump($result);
// @before :: Gzhegow\Router\Handler\Demo\Middleware\Demo1stMiddleware::__invoke
// @before :: Gzhegow\Router\Handler\Demo\Middleware\Demo2ndMiddleware::__invoke
// string(62) "Gzhegow\Router\Handler\Demo\Controller\DemoController::runtime"
// string(59) "Gzhegow\Router\Handler\Demo\Fallback\DemoFallback::__invoke"
// string(66) "Gzhegow\Router\Handler\Demo\Fallback\DemoRuntimeFallback::__invoke"
// @after :: Gzhegow\Router\Handler\Demo\Middleware\Demo2ndMiddleware::__invoke
// @after :: Gzhegow\Router\Handler\Demo\Middleware\Demo1stMiddleware::__invoke
// bool(true)
echo PHP_EOL;

// > так можно сгенерировать ссылки для зарегистрированных маршрутов по именам
echo 'Case 7:' . PHP_EOL;
$instances = [];
$instances[ 'a' ] = $router->matchFirstByName('user.main');
//
$names = [];
$names[ 'b' ] = 'user.main';
$names[ 'c' ] = 'user.main';
//
$routes = $instances + $names;
//
$attributes = [];
$ids = [];
$ids[ 'a' ] = 1;
$ids[ 'b' ] = 2;
$ids[ 'c' ] = 3;
//
$attributes = [ 'id' => $ids ];
// > можно передать либо список объектов (instance of Route::class) и/или список строк (route `name`)
$result = $router->urls($routes, $attributes);
var_dump($result);
// array(3) {
//   ["a"]=>
//   string(19) "/api/v1/user/1/main"
//   ["b"]=>
//   string(19) "/api/v1/user/2/main"
//   ["c"]=>
//   string(19) "/api/v1/user/3/main"
// }
```