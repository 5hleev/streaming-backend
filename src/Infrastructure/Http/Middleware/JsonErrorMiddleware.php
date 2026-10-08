<?php
declare(strict_types=1);

namespace App\Infrastructure\Http\Middleware;

use App\Domain\Exception\DomainException;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Exception\HttpException;

/** Единая точка преобразования исключений в JSON-ответы. */
final class JsonErrorMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly ResponseFactoryInterface $responses)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (DomainException $e) {
            return $this->error($e->httpStatus(), $e->getMessage());
        } catch (HttpException $e) {
            return $this->error($e->getCode(), $e->getMessage());
        } catch (\Throwable $e) {
            error_log((string) $e);
            return $this->error(500, 'Внутренняя ошибка сервера');
        }
    }

    private function error(int $status, string $message): ResponseInterface
    {
        $response = $this->responses->createResponse($status)->withHeader('Content-Type', 'application/json; charset=utf-8');
        $response->getBody()->write(json_encode(['error' => $message], JSON_UNESCAPED_UNICODE));

        return $response;
    }
}
