<?php
declare(strict_types=1);

namespace App\Interfaces\Persistence;

use App\Domain\Entity\Subscription;
use App\Domain\Repository\SubscriptionRepository;

final class PdoSubscriptionRepository implements SubscriptionRepository
{
    public function __construct(private readonly \PDO $pdo)
    {
    }

    public function save(Subscription $s): Subscription
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO subscriptions (user_id, plan_id, starts_at, ends_at)
             VALUES (:u, :p, :s, :e) RETURNING id'
        );
        $stmt->execute([
            'u' => $s->userId,
            'p' => $s->planId,
            's' => Sql::timestamp($s->startsAt),
            'e' => Sql::timestamp($s->endsAt),
        ]);

        return new Subscription((int) $stmt->fetchColumn(), $s->userId, $s->planId, $s->startsAt, $s->endsAt);
    }

    public function findActiveByUser(int $userId, \DateTimeImmutable $moment): ?Subscription
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM subscriptions
             WHERE user_id = :u AND starts_at <= :m AND ends_at > :m
             ORDER BY ends_at DESC LIMIT 1'
        );
        $stmt->execute(['u' => $userId, 'm' => Sql::timestamp($moment)]);
        $row = $stmt->fetch();

        return $row ? new Subscription(
            (int) $row['id'],
            (int) $row['user_id'],
            (int) $row['plan_id'],
            new \DateTimeImmutable($row['starts_at']),
            new \DateTimeImmutable($row['ends_at']),
        ) : null;
    }
}
