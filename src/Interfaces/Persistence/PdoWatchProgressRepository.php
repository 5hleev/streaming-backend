<?php
declare(strict_types=1);

namespace App\Interfaces\Persistence;

use App\Domain\Entity\WatchProgress;
use App\Domain\Repository\WatchProgressRepository;

final class PdoWatchProgressRepository implements WatchProgressRepository
{
    public function __construct(private readonly \PDO $pdo)
    {
    }

    public function upsert(WatchProgress $p): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO watch_progress (user_id, content_id, position_seconds, updated_at)
             VALUES (:u, :c, :p, :t)
             ON CONFLICT (user_id, content_id)
             DO UPDATE SET position_seconds = EXCLUDED.position_seconds, updated_at = EXCLUDED.updated_at'
        );
        $stmt->execute([
            'u' => $p->userId,
            'c' => $p->contentId,
            'p' => $p->positionSeconds,
            't' => Sql::timestamp($p->updatedAt),
        ]);
    }

    public function findByUser(int $userId, int $limit): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM watch_progress WHERE user_id = :u ORDER BY updated_at DESC LIMIT :l'
        );
        $stmt->bindValue('u', $userId, \PDO::PARAM_INT);
        $stmt->bindValue('l', $limit, \PDO::PARAM_INT);
        $stmt->execute();

        return array_map(
            static fn (array $r) => new WatchProgress(
                (int) $r['user_id'],
                (int) $r['content_id'],
                (int) $r['position_seconds'],
                new \DateTimeImmutable($r['updated_at']),
            ),
            $stmt->fetchAll()
        );
    }
}
