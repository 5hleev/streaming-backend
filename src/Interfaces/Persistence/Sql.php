<?php
declare(strict_types=1);

namespace App\Interfaces\Persistence;

/** Преобразования значений домена в формат, понятный SQL. */
final class Sql
{
    public static function timestamp(\DateTimeImmutable $d): string
    {
        return $d->format('Y-m-d H:i:sP');
    }
}
