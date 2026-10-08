<?php
declare(strict_types=1);

namespace App\Application\UseCase;

use App\Domain\Entity\WatchProgress;
use App\Domain\Repository\WatchProgressRepository;

final class GetWatchHistory
{
    public function __construct(private readonly WatchProgressRepository $progress)
    {
    }

    /** @return WatchProgress[] */
    public function execute(int $userId, int $limit = 50): array
    {
        return $this->progress->findByUser($userId, min(100, max(1, $limit)));
    }
}
