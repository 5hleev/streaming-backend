<?php
declare(strict_types=1);

namespace Tests\Fake;

use App\Domain\Entity\Subscription;
use App\Domain\Entity\User;
use App\Domain\Repository\SubscriptionRepository;

final class InMemorySubscriptionRepository implements SubscriptionRepository
{
    /** @var Subscription[] */
    public array $items = [];

    public function save(Subscription $subscription): Subscription
    {
        $this->items[] = $subscription;

        return $subscription;
    }

    public function findActiveByUser(int $userId, \DateTimeImmutable $moment): ?Subscription
    {
        foreach ($this->items as $s) {
            if ($s->userId === $userId && $s->isActiveAt($moment)) {
                return $s;
            }
        }

        return null;
    }
}
