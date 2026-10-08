<?php
declare(strict_types=1);

namespace Tests\Fake;

use App\Domain\Entity\User;
use App\Domain\Repository\UserRepository;

final class InMemoryUserRepository implements UserRepository
{
    /** @var array<int,User> */
    public array $items = [];

    public function save(User $user): User
    {
        $user = $user->withId(count($this->items) + 1);
        $this->items[$user->id] = $user;

        return $user;
    }

    public function findById(int $id): ?User
    {
        return $this->items[$id] ?? null;
    }

    public function findByEmail(string $email): ?User
    {
        foreach ($this->items as $u) {
            if ($u->email === $email) {
                return $u;
            }
        }

        return null;
    }
}
