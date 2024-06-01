<?php

use Gzhegow\Router\Router;
use Gzhegow\Router\RouterFactory;
use Gzhegow\Router\Cache\RouterCache;
use Gzhegow\Router\Handler\Action\CorsAction;
use Gzhegow\Router\Contract\RouterMatchContract;
use Gzhegow\Router\Contract\RouterDispatchContract;
use Gzhegow\Router\Handler\Middleware\CorsMiddleware;
use Gzhegow\Router\Handler\Demo\Fallback\DemoFallback;
use Gzhegow\Router\Handler\Demo\Controller\DemoController;
use Gzhegow\Router\Handler\Demo\Middleware\DemoMiddleware;
use function Gzhegow\Router\_php_dump;


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
// set_exception_handler(function ($e) {
//     var_dump($e);
//     die();
// });
set_exception_handler('dd');

// > создаем роутер
$router = (new RouterFactory())->newRouter();

// >>> Ставим настройки роутера
$router->setSettings(
    $registerAllowObjectsAndClosures = false,
    $compileAllowTrailingSlash = false,
    $dispatchIgnoreMethod = false,
    $dispatchTrailingSlashMode = Router::TRAILING_SLASH_AS_IS
// $dispatchTrailingSlashMode = Router::TRAILING_SLASH_AS_IS // > оставить как есть
// $dispatchTrailingSlashMode = Router::TRAILING_SLASH_ALWAYS, // > при вызове маршрута добавлять к нему trailing-slash
// $dispatchTrailingSlashMode = Router::TRAILING_SLASH_NEVER, // > при вызове маршрута удалить trailing-slash
);

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
$router->remember(function (Router $router) {
    // > добавляем паттерн, который можно использовать в маршрутах
    $router->addPattern('{id}', '[0-9]+');

    // > добавляет Middleware по пути (они отработают даже если маршрут не найден, но путь начинался с указанного)
    // $router->addPathMiddleware('/api/v1/user', CorsMiddleware::class);
    // $router->addPathMiddleware('/api/v1/user', DemoMiddleware::class);

    // > добавляет Fallback по пути (если во время маршрута будет исключение или роута не будет (NotFoundException) - запустится это действие)
    // > несколько Fallback запустятся один за другим, передавая результат по цепи, если в итоге результат NULL - исключение будет брошено снова
    $router->addPathFallback('/api/v1/user', DemoFallback::class);

    // > к маршрутам можно привязывать теги, на теги подключать Middleware и Fallback, также по тегам можно искать маршруты
    // $router->addTagMiddleware('user', DemoMiddleware::class);
    // $router->addTagFallback('user', DemoFallback::class);

    // > для того, чтобы зарегистрировать маршруты удобно использовать группировку
    $router->group()
        ->middleware([ CorsMiddleware::class, DemoMiddleware::class ])
        ->tag('user')
        ->register(function (Router $router) {
            $router->route('/api/v1/user/{id}/main', 'OPTIONS', $action = CorsAction::class);
            $router->route('/api/v1/user/{id}/main', 'GET', $action = [ DemoController::class, 'main' ], $name = 'user.main');
            //
            $router->route('/api/v1/user/{id}/logic', 'GET', $action = [ DemoController::class, 'logic' ], $name = 'user.logic');
            $router->route('/api/v1/user/{id}/runtime', 'GET', $action = [ DemoController::class, 'runtime' ]);
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

echo 'Case 1:' . PHP_EOL;
$contract = RouterMatchContract::from([ 'name' => 'user.main' ]);
$routes = $router->matchByContract($contract);
foreach ( $routes as $id => $route ) {
    var_dump($id, _php_dump($route));
}
// string(52) "[1,"{ object(Gzhegow\\Router\\Route\\Route # 17) }"]"
echo PHP_EOL;

// > так можно запустить выполнение маршрута в вашем файле index.php, на который указывает apache2/nginx
echo 'Case 2:' . PHP_EOL;
$contract = RouterDispatchContract::from([ 'GET', '/api/v1/user/1/main' ]);
$result = $router->dispatch($contract);
var_dump($result);
// Case 2:
// @before :: Gzhegow\Router\Handler\Demo\Middleware\DemoMiddleware::__invoke
// string(59) "Gzhegow\Router\Handler\Demo\Controller\DemoController::main"
// @after :: Gzhegow\Router\Handler\Demo\Middleware\DemoMiddleware::__invoke
// int(1)
echo PHP_EOL;

// > вот такого маршрута нет, запустится ранее указанный pathFallback
echo 'Case 3:' . PHP_EOL;
$contract = RouterDispatchContract::from([ 'GET', '/api/v1/user/1/not-found' ]);
$result = $router->dispatch($contract);
var_dump($result);
// Case 2:
// string(59) "Gzhegow\Router\Handler\Demo\Fallback\DemoFallback::__invoke"
// bool(false)
echo PHP_EOL;

// > так можно сгенерировать ссылки для зарегистрированных маршрутов по именам
echo 'Case 4:' . PHP_EOL;
$names = [];
$names[ 'a' ] = 'user.main';
$names[ 'b' ] = 'user.main';
$names[ 'c' ] = 'user.main';
$ids = [];
$ids[ 'a' ] = 1;
$ids[ 'b' ] = 2;
$ids[ 'c' ] = 3;
$result = $router->urls($names, [ 'id' => $ids ]);
var_dump($result);
// array(3) {
//   ["a"]=>
//   string(19) "/api/v1/user/1/main"
//   ["b"]=>
//   string(19) "/api/v1/user/2/main"
//   ["c"]=>
//   string(19) "/api/v1/user/3/main"
// }
