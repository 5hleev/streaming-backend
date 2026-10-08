<?php
declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Application\Port\Clock;
use App\Application\Port\TokenService;
use App\Domain\Entity\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

final class JwtTokenService implements TokenService
{
    public function __construct(
        private readonly string $secret,
        private readonly Clock $clock,
        private readonly int $ttlSeconds = 3600,
    ) {
    }

    public function issue(User $user): string
    {
        $now = $this->clock->now()->getTimestamp();

        return JWT::encode([
            'sub' => (string) $user->id,
            'role' => $user->role,
            'iat' => $now,
            'exp' => $now + $this->ttlSeconds,
        ], $this->secret, 'HS256');
    }

    public function verify(string $token): ?array
    {
        try {
            JWT::$timestamp = $this->clock->now()->getTimestamp();
            $payload = JWT::decode($token, new Key($this->secret, 'HS256'));
        } catch (\Throwable) {
            return null;
        }

        return ['userId' => (int) $payload->sub, 'role' => (string) $payload->role];
    }
}
