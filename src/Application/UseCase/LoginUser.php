<?php
declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\Port\PasswordHasher;
use App\Application\Port\TokenService;
use App\Domain\Exception\AuthenticationException;
use App\Domain\Repository\UserRepository;

final class LoginUser
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly PasswordHasher $hasher,
        private readonly TokenService $tokens,
    ) {
    }

    /** @return string JWT-токен */
    public function execute(string $email, string $password): string
    {
        $user = $this->users->findByEmail(mb_strtolower(trim($email)));
        // Одинаковая ошибка для «нет пользователя» и «неверный пароль» — не раскрываем, какой email зарегистрирован.
        if ($user === null || !$this->hasher->verify($password, $user->passwordHash)) {
            throw new AuthenticationException('Неверный email или пароль');
        }

        return $this->tokens->issue($user);
    }
}
