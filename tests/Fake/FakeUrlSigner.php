<?php
declare(strict_types=1);

namespace Tests\Fake;

use App\Application\Port\UrlSigner;

final class FakeUrlSigner implements UrlSigner
{
    public function sign(string $resource, int $ttlSeconds): array
    {
        return ['exp' => 1000 + $ttlSeconds, 'sig' => 'sig-' . $resource];
    }

    public function verify(string $resource, int $exp, string $sig): bool
    {
        return $sig === 'sig-' . $resource;
    }
}
