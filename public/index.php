<?php
declare(strict_types=1);

use App\Infrastructure\Http\Middleware\JsonErrorMiddleware;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

$container = (require __DIR__ . '/../config/container.php')();
AppFactory::setContainer($container);
$app = AppFactory::create();

$app->addBodyParsingMiddleware();
(require __DIR__ . '/../config/routes.php')($app);
$app->addRoutingMiddleware();
// добавляется последним => выполняется первым и ловит исключения всех остальных слоёв
$app->add(new JsonErrorMiddleware($app->getResponseFactory()));

$app->run();
