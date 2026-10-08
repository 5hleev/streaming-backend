<?php
declare(strict_types=1);

use App\Application\Port\TokenService;
use App\Interfaces\Http\Controller\AuthController;
use App\Interfaces\Http\Controller\ContentController;
use App\Interfaces\Http\Controller\StreamController;
use App\Interfaces\Http\Controller\UserController;
use App\Infrastructure\Http\Middleware\AuthMiddleware;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return static function (App $app): void {
    $tokens = $app->getContainer()->get(TokenService::class);
    $auth = new AuthMiddleware($tokens);
    $admin = new AuthMiddleware($tokens, true);

    // публичные
    $app->post('/auth/register', [AuthController::class, 'register']);
    $app->post('/auth/login', [AuthController::class, 'login']);
    $app->get('/content', [ContentController::class, 'list']);
    // доступ по подписанной ссылке (проверка внутри контроллера)
    $app->get('/stream/{id:[0-9]+}/{file}', [StreamController::class, 'serve']);

    // для авторизованного пользователя
    $app->group('', function (RouteCollectorProxy $g) {
        $g->post('/content/{id:[0-9]+}/stream', [StreamController::class, 'request']);
        $g->post('/subscriptions', [UserController::class, 'subscribe']);
        $g->put('/content/{id:[0-9]+}/progress', [UserController::class, 'saveProgress']);
        $g->get('/me/history', [UserController::class, 'history']);
    })->add($auth);

    // только администратор
    $app->group('', function (RouteCollectorProxy $g) {
        $g->post('/content', [ContentController::class, 'create']);
        $g->post('/content/{id:[0-9]+}/media', [ContentController::class, 'uploadMedia']);
    })->add($admin);
};
