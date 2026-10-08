<?php
declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Application\Port\Clock;
use App\Application\Port\UrlSigner;

final class HmacUrlSigner implements UrlSigner
{
    public function __construct(private readonly string $secret, private readonly Clock $clock)
    {
    }

    public function sign(string $resource, int $ttlSeconds): array
    {
        $exp = $this->clock->now()->getTimestamp() + $ttlSeconds;

        return ['exp' => $exp, 'sig' => $this->mac($resource, $exp)];
    }

    public function verify(string $resource, int $exp, string $sig): bool
    {
        return $exp >= $this->clock->now()->getTimestamp()
            && hash_equals($this->mac($resource, $exp), $sig);
    }

    private function mac(string $resource, int $exp): string
    {
        return hash_hmac('sha256', $resource . '|' . $exp, $this->secret);
    }
}
