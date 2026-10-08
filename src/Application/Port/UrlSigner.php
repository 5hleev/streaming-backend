<?php
declare(strict_types=1);

namespace App\Application\Port;

interface UrlSigner
{
    /** @return array{exp:int, sig:string} */
    public function sign(string $resource, int $ttlSeconds): array;

    public function verify(string $resource, int $exp, string $sig): bool;
}
