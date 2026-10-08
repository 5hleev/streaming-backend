<?php
declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\Port\Clock;
use App\Application\Port\UrlSigner;
use App\Domain\Exception\AccessDeniedException;
use App\Domain\Exception\NotFoundException;
use App\Domain\Repository\ContentRepository;
use App\Domain\Repository\SubscriptionRepository;

/** Проверяет право просмотра и выдаёт подписанные параметры для доступа к HLS-манифесту. */
final class RequestStream
{
    public const MANIFEST = 'master.m3u8';

    public function __construct(
        private readonly ContentRepository $contents,
        private readonly SubscriptionRepository $subscriptions,
        private readonly UrlSigner $signer,
        private readonly Clock $clock,
        private readonly int $ttlSeconds,
    ) {
    }

    /** @return array{contentId:int, file:string, exp:int, sig:string} */
    public function execute(int $userId, int $contentId): array
    {
        $content = $this->contents->findById($contentId);
        if ($content === null || !$content->isPublished()) {
            throw new NotFoundException('Контент не найден или ещё не опубликован');
        }

        $subscription = $this->subscriptions->findActiveByUser($userId, $this->clock->now());
        if (!$content->isAccessibleWith($subscription)) {
            throw new AccessDeniedException('Для просмотра требуется активная подписка');
        }

        $signed = $this->signer->sign(self::resource($contentId), $this->ttlSeconds);

        return ['contentId' => $contentId, 'file' => self::MANIFEST, 'exp' => $signed['exp'], 'sig' => $signed['sig']];
    }

    /** Подпись выдаётся на весь контент, а не на отдельный файл: плеер сам запрашивает сегменты. */
    public static function resource(int $contentId): string
    {
        return 'content:' . $contentId;
    }
}
