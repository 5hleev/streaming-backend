<?php
declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\Port\MediaStorage;
use App\Domain\Entity\TranscodeJob;
use App\Domain\Exception\NotFoundException;
use App\Domain\Exception\ValidationException;
use App\Domain\Repository\ContentRepository;
use App\Domain\Repository\TranscodeJobRepository;

/** Принимает исходный видеофайл и ставит задачу транскодирования в очередь. */
final class AttachMedia
{
    private const ALLOWED_EXTENSIONS = ['mp4', 'mkv', 'mov', 'avi', 'webm'];

    public function __construct(
        private readonly ContentRepository $contents,
        private readonly TranscodeJobRepository $jobs,
        private readonly MediaStorage $storage,
    ) {
    }

    public function execute(int $contentId, string $tmpFile, string $originalName): TranscodeJob
    {
        $content = $this->contents->findById($contentId)
            ?? throw new NotFoundException('Контент не найден');

        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            throw new ValidationException('Неподдерживаемый формат файла');
        }

        $sourcePath = $this->storage->saveSource($contentId, $tmpFile, $extension);
        $this->contents->save($content->markProcessing($sourcePath));

        return $this->jobs->save(new TranscodeJob(null, $contentId));
    }
}
