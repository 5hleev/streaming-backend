<?php
declare(strict_types=1);

namespace Tests\Unit;

use App\Application\UseCase\RequestStream;
use App\Domain\Entity\Content;
use App\Domain\Entity\Subscription;
use App\Domain\Exception\AccessDeniedException;
use App\Domain\Exception\NotFoundException;
use PHPUnit\Framework\TestCase;
use Tests\Fake\FakeUrlSigner;
use Tests\Fake\FixedClock;
use Tests\Fake\InMemoryContentRepository;
use Tests\Fake\InMemorySubscriptionRepository;

final class RequestStreamTest extends TestCase
{
    private InMemoryContentRepository $contents;
    private InMemorySubscriptionRepository $subs;
    private FixedClock $clock;
    private RequestStream $useCase;

    protected function setUp(): void
    {
        $this->contents = new InMemoryContentRepository();
        $this->subs = new InMemorySubscriptionRepository();
        $this->clock = new FixedClock(new \DateTimeImmutable('2026-10-01 12:00:00'));
        $this->useCase = new RequestStream($this->contents, $this->subs, new FakeUrlSigner(), $this->clock, 300);
    }

    private function publish(bool $free): int
    {
        $c = $this->contents->save(new Content(null, 'Фильм', '', 'drama', 2020, $free));

        return $this->contents->save($c->markPublished('hls/1'))->id;
    }

    public function testFreeContentIsAvailableWithoutSubscription(): void
    {
        $id = $this->publish(true);

        $result = $this->useCase->execute(1, $id);

        $this->assertSame('master.m3u8', $result['file']);
        $this->assertSame(1300, $result['exp']);
    }

    public function testPaidContentRequiresActiveSubscription(): void
    {
        $id = $this->publish(false);

        $this->expectException(AccessDeniedException::class);
        $this->useCase->execute(1, $id);
    }

    public function testPaidContentAvailableWithActiveSubscription(): void
    {
        $id = $this->publish(false);
        $now = $this->clock->now();
        $this->subs->save(new Subscription(null, 1, 1, $now->modify('-1 day'), $now->modify('+10 days')));

        $this->assertSame($id, $this->useCase->execute(1, $id)['contentId']);
    }

    public function testExpiredSubscriptionDoesNotGrantAccess(): void
    {
        $id = $this->publish(false);
        $now = $this->clock->now();
        $this->subs->save(new Subscription(null, 1, 1, $now->modify('-40 days'), $now->modify('-10 days')));

        $this->expectException(AccessDeniedException::class);
        $this->useCase->execute(1, $id);
    }

    public function testUnpublishedContentIsNotFound(): void
    {
        $draft = $this->contents->save(new Content(null, 'Черновик', '', 'drama', 2020, true));

        $this->expectException(NotFoundException::class);
        $this->useCase->execute(1, $draft->id);
    }
}
