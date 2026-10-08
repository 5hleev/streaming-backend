<?php
declare(strict_types=1);

namespace App\Interfaces\Persistence;

use App\Domain\Entity\Plan;
use App\Domain\Repository\PlanRepository;

final class PdoPlanRepository implements PlanRepository
{
    public function __construct(private readonly \PDO $pdo)
    {
    }

    public function findById(int $id): ?Plan
    {
        $stmt = $this->pdo->prepare('SELECT * FROM plans WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? new Plan((int) $row['id'], $row['name'], (int) $row['price_cents'], (int) $row['duration_days']) : null;
    }
}
