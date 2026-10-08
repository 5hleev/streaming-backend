<?php
declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Exception\ValidationException;

final class WatchProgress
{
    public function __construct(
        public readonly int $userId,
        public readonly int $contentId,
        public readonly int $positionSeconds,
        public readonly \DateTimeImmutable $updatedAt,
    ) {
        if ($positionSeconds < 0) {
            throw new ValidationException('Позиция просмотра не может быть отрицательной');
        }
    }
}
