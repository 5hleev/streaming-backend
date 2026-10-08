<?php
declare(strict_types=1);

namespace App\Domain\Entity;

final class User
{
    public const ROLE_VIEWER = 'viewer';
    public const ROLE_ADMIN = 'admin';

    public function __construct(
        public readonly ?int $id,
        public readonly string $email,
        public readonly string $passwordHash,
        public readonly string $role = self::ROLE_VIEWER,
    ) {
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function withId(int $id): self
    {
        return new self($id, $this->email, $this->passwordHash, $this->role);
    }
}
