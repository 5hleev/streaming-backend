<?php
declare(strict_types=1);

namespace App\Domain\Entity;

final class TranscodeJob
{
    public const PENDING = 'pending';
    public const RUNNING = 'running';
    public const DONE = 'done';
    public const FAILED = 'failed';

    public function __construct(
        public readonly ?int $id,
        public readonly int $contentId,
        public readonly string $status = self::PENDING,
        public readonly ?string $error = null,
    ) {
    }

    public function done(): self
    {
        return new self($this->id, $this->contentId, self::DONE);
    }

    public function failed(string $error): self
    {
        return new self($this->id, $this->contentId, self::FAILED, $error);
    }
}
