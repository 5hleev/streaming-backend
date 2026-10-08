<?php
declare(strict_types=1);

namespace App\Infrastructure\Http\Middleware;

use App\Application\Port\TokenService;
use App\Domain\Entity\User;
use App\Domain\Exception\AccessDeniedException;
use App\Domain\Exception\AuthenticationException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class AuthMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly TokenService $tokens, private readonly bool $adminOnly = false)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $header = $request->getHeaderLine('Authorization');
        if (!preg_match('/^Bearer\s+(\S+)$/i', $header, $m)) {
            throw new AuthenticationException('Требуется авторизация');
        }

        $claims = $this->tokens->verify($m[1]) ?? throw new AuthenticationException('Недействительный или просроченный токен');

        if ($this->adminOnly && $claims['role'] !== User::ROLE_ADMIN) {
            throw new AccessDeniedException('Требуются права администратора');
        }

        return $handler->handle(
            $request->withAttribute('userId', $claims['userId'])->withAttribute('role', $claims['role'])
        );
    }
}
