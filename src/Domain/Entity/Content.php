<?php
declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Exception\ValidationException;

final class Content
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_FAILED = 'failed';

    public function __construct(
        public readonly ?int $id,
        public readonly string $title,
        public readonly string $description,
        public readonly string $genre,
        public readonly int $releaseYear,
        public readonly bool $isFree,
        public readonly string $status = self::STATUS_DRAFT,
        public readonly ?string $sourcePath = null,
        public readonly ?string $hlsPath = null,
    ) {
        if (trim($title) === '') {
            throw new ValidationException('Название не может быть пустым');
        }
        if ($releaseYear < 1888 || $releaseYear > 2100) {
            throw new ValidationException('Некорректный год выпуска');
        }
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    /**
     * Бизнес-правило доступа: бесплатный контент доступен всем,
     * платный — только при наличии активной подписки.
     */
    public function isAccessibleWith(?Subscription $activeSubscription): bool
    {
        return $this->isFree || $activeSubscription !== null;
    }

    public function withId(int $id): self
    {
        return $this->copy(id: $id);
    }

    public function markProcessing(string $sourcePath): self
    {
        return $this->copy(status: self::STATUS_PROCESSING, sourcePath: $sourcePath);
    }

    public function markPublished(string $hlsPath): self
    {
        return $this->copy(status: self::STATUS_PUBLISHED, hlsPath: $hlsPath);
    }

    public function markFailed(): self
    {
        return $this->copy(status: self::STATUS_FAILED);
    }

    private function copy(
        ?int $id = null,
        ?string $status = null,
        ?string $sourcePath = null,
        ?string $hlsPath = null,
    ): self {
        return new self(
            $id ?? $this->id,
            $this->title,
            $this->description,
            $this->genre,
            $this->releaseYear,
            $this->isFree,
            $status ?? $this->status,
            $sourcePath ?? $this->sourcePath,
            $hlsPath ?? $this->hlsPath,
        );
    }
}
