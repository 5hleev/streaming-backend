<?php
declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\Port\Clock;
use App\Domain\Entity\WatchProgress;
use App\Domain\Exception\NotFoundException;
use App\Domain\Repository\ContentRepository;
use App\Domain\Repository\WatchProgressRepository;

final class SaveProgress
{
    public function __construct(
        private readonly ContentRepository $contents,
        private readonly WatchProgressRepository $progress,
        private readonly Clock $clock,
    ) {
    }

    public function execute(int $userId, int $contentId, int $positionSeconds): void
    {
        if ($this->contents->findById($contentId) === null) {
            throw new NotFoundException('Контент не найден');
        }
        $this->progress->upsert(new WatchProgress($userId, $contentId, $positionSeconds, $this->clock->now()));
    }
}
