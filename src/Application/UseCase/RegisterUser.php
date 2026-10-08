<?php
declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\Port\PasswordHasher;
use App\Domain\Entity\User;
use App\Domain\Exception\ConflictException;
use App\Domain\Exception\ValidationException;
use App\Domain\Repository\UserRepository;

final class RegisterUser
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly PasswordHasher $hasher,
    ) {
    }

    public function execute(string $email, string $password): User
    {
        $email = mb_strtolower(trim($email));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new ValidationException('Некорректный email');
        }
        if (mb_strlen($password) < 8) {
            throw new ValidationException('Пароль должен содержать не менее 8 символов');
        }
        if ($this->users->findByEmail($email) !== null) {
            throw new ConflictException('Пользователь с таким email уже существует');
        }

        return $this->users->save(new User(null, $email, $this->hasher->hash($password)));
    }
}
