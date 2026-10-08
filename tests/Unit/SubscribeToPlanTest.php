<?php
declare(strict_types=1);

namespace Tests\Unit;

use App\Application\UseCase\SubscribeToPlan;
use App\Domain\Entity\Plan;
use App\Domain\Exception\NotFoundException;
use App\Domain\Repository\PlanRepository;
use PHPUnit\Framework\TestCase;
use Tests\Fake\FixedClock;
use Tests\Fake\InMemorySubscriptionRepository;

final class SubscribeToPlanTest extends TestCase
{
    private function plans(): PlanRepository
    {
        return new class implements PlanRepository {
            public function findById(int $id): ?Plan
            {
                return $id === 1 ? new Plan(1, 'Basic', 29900, 30) : null;
            }
        };
    }

    public function testCreatesThirtyDaySubscription(): void
    {
        $now = new \DateTimeImmutable('2026-10-01 00:00:00');
        $uc = new SubscribeToPlan($this->plans(), new InMemorySubscriptionRepository(), new FixedClock($now));

        $sub = $uc->execute(7, 1);

        $this->assertEquals($now, $sub->startsAt);
        $this->assertEquals($now->modify('+30 days'), $sub->endsAt);
    }

    public function testRenewalStartsWhenCurrentSubscriptionEnds(): void
    {
        $now = new \DateTimeImmutable('2026-10-01 00:00:00');
        $uc = new SubscribeToPlan($this->plans(), new InMemorySubscriptionRepository(), new FixedClock($now));

        $first = $uc->execute(7, 1);
        $second = $uc->execute(7, 1);

        $this->assertEquals($first->endsAt, $second->startsAt);
    }

    public function testUnknownPlan(): void
    {
        $uc = new SubscribeToPlan($this->plans(), new InMemorySubscriptionRepository(), new FixedClock(new \DateTimeImmutable()));

        $this->expectException(NotFoundException::class);
        $uc->execute(7, 99);
    }
}
