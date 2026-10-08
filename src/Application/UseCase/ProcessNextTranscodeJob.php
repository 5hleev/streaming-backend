<?php
declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\Port\MediaStorage;
use App\Application\Port\Transcoder;
use App\Domain\Repository\ContentRepository;
use App\Domain\Repository\TranscodeJobRepository;

/** Вызывается воркером: берёт одну задачу из очереди и выполняет её. */
final class ProcessNextTranscodeJob
{
    public function __construct(
        private readonly TranscodeJobRepository $jobs,
        private readonly ContentRepository $contents,
        private readonly MediaStorage $storage,
        private readonly Transcoder $transcoder,
    ) {
    }

    /** @return bool true, если задача была обработана (успешно или нет); false — очередь пуста */
    public function execute(): bool
    {
        $job = $this->jobs->claimNext();
        if ($job === null) {
            return false;
        }

        $content = $this->contents->findById($job->contentId);
        if ($content === null || $content->sourcePath === null) {
            $this->jobs->save($job->failed('Контент или исходный файл не найден'));
            return true;
        }

        try {
            $this->transcoder->toHls(
                $this->storage->sourceAbsolutePath($content->sourcePath),
                $this->storage->hlsDirectory($job->contentId),
            );
            $this->contents->save($content->markPublished($this->storage->hlsRelativeDirectory($job->contentId)));
            $this->jobs->save($job->done());
        } catch (\Throwable $e) {
            $this->contents->save($content->markFailed());
            $this->jobs->save($job->failed($e->getMessage()));
        }

        return true;
    }
}
