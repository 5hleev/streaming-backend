<?php
declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Subscription;

interface SubscriptionRepository
{
    public function save(Subscription $subscription): Subscription;
    public function findActiveByUser(int $userId, \DateTimeImmutable $moment): ?Subscription;
}
