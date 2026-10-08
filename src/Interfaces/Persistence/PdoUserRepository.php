<?php
declare(strict_types=1);

namespace App\Interfaces\Persistence;

use App\Domain\Entity\User;
use App\Domain\Exception\ConflictException;
use App\Domain\Repository\UserRepository;

final class PdoUserRepository implements UserRepository
{
    public function __construct(private readonly \PDO $pdo)
    {
    }

    public function save(User $user): User
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO users (email, password_hash, role) VALUES (:email, :hash, :role) RETURNING id'
        );
        try {
            $stmt->execute(['email' => $user->email, 'hash' => $user->passwordHash, 'role' => $user->role]);
        } catch (\PDOException $e) {
            // 23505 — нарушение уникальности: параллельная регистрация того же email
            if ($e->getCode() === '23505') {
                throw new ConflictException('Пользователь с таким email уже существует');
            }
            throw $e;
        }

        return $user->withId((int) $stmt->fetchColumn());
    }

    public function findById(int $id): ?User
    {
        return $this->fetch('SELECT * FROM users WHERE id = :v', $id);
    }

    public function findByEmail(string $email): ?User
    {
        return $this->fetch('SELECT * FROM users WHERE email = :v', $email);
    }

    private function fetch(string $sql, int|string $value): ?User
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['v' => $value]);
        $row = $stmt->fetch();

        return $row ? new User((int) $row['id'], $row['email'], $row['password_hash'], $row['role']) : null;
    }
}
