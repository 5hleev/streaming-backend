<?php
declare(strict_types=1);

namespace Tests\Fake;

use App\Application\Port\PasswordHasher;

final class PlainPasswordHasher implements PasswordHasher
{
    public function hash(string $plain): string
    {
        return 'h:' . $plain;
    }

    public function verify(string $plain, string $hash): bool
    {
        return $hash === 'h:' . $plain;
    }
}
