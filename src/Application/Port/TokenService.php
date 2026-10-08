<?php
declare(strict_types=1);

namespace App\Application\Port;

use App\Domain\Entity\User;

interface TokenService
{
    public function issue(User $user): string;

    /** @return array{userId:int, role:string}|null null, если токен недействителен или просрочен */
    public function verify(string $token): ?array;
}
