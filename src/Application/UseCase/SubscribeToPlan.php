<?php
declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\Port\Clock;
use App\Domain\Entity\Subscription;
use App\Domain\Exception\NotFoundException;
use App\Domain\Repository\PlanRepository;
use App\Domain\Repository\SubscriptionRepository;

final class SubscribeToPlan
{
    public function __construct(
        private readonly PlanRepository $plans,
        private readonly SubscriptionRepository $subscriptions,
        private readonly Clock $clock,
    ) {
    }

    public function execute(int $userId, int $planId): Subscription
    {
        $plan = $this->plans->findById($planId) ?? throw new NotFoundException('Тариф не найден');
        $now = $this->clock->now();

        // Если есть активная подписка — новая начинается после её окончания, а не перекрывает её.
        $active = $this->subscriptions->findActiveByUser($userId, $now);
        $start = $active !== null ? $active->endsAt : $now;

        return $this->subscriptions->save(Subscription::forPlan($userId, $plan, $start));
    }
}
