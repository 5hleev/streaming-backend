<?php
declare(strict_types=1);

namespace App\Domain\Entity;

final class Subscription
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $userId,
        public readonly int $planId,
        public readonly \DateTimeImmutable $startsAt,
        public readonly \DateTimeImmutable $endsAt,
    ) {
    }

    /** Бизнес-правило: подписка активна, пока момент времени лежит в [startsAt, endsAt). */
    public function isActiveAt(\DateTimeImmutable $moment): bool
    {
        return $moment >= $this->startsAt && $moment < $this->endsAt;
    }

    public static function forPlan(int $userId, Plan $plan, \DateTimeImmutable $now): self
    {
        return new self(null, $userId, $plan->id, $now, $now->modify("+{$plan->durationDays} days"));
    }
}
