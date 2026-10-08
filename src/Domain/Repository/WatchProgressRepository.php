<?php
declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\WatchProgress;

interface WatchProgressRepository
{
    public function upsert(WatchProgress $progress): void;

    /** @return WatchProgress[] отсортировано по updatedAt, новые первыми */
    public function findByUser(int $userId, int $limit): array;
}
