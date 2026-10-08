<?php
declare(strict_types=1);

namespace App\Interfaces\Http\Controller;

use App\Application\UseCase\LoginUser;
use App\Application\UseCase\RegisterUser;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class AuthController extends BaseController
{
    public function __construct(private readonly RegisterUser $register, private readonly LoginUser $login)
    {
    }

    public function register(ServerRequestInterface $req, ResponseInterface $res): ResponseInterface
    {
        $b = $this->body($req);
        $user = $this->register->execute((string) ($b['email'] ?? ''), (string) ($b['password'] ?? ''));

        return $this->json($res, ['id' => $user->id, 'email' => $user->email], 201);
    }

    public function login(ServerRequestInterface $req, ResponseInterface $res): ResponseInterface
    {
        $b = $this->body($req);
        $token = $this->login->execute((string) ($b['email'] ?? ''), (string) ($b['password'] ?? ''));

        return $this->json($res, ['token' => $token]);
    }
}
