<?php
declare(strict_types=1);

namespace App\Interfaces\Persistence;

use App\Domain\Entity\TranscodeJob;
use App\Domain\Repository\TranscodeJobRepository;

final class PdoTranscodeJobRepository implements TranscodeJobRepository
{
    public function __construct(private readonly \PDO $pdo)
    {
    }

    public function save(TranscodeJob $job): TranscodeJob
    {
        if ($job->id === null) {
            $stmt = $this->pdo->prepare(
                'INSERT INTO transcode_jobs (content_id, status) VALUES (:c, :s) RETURNING id'
            );
            $stmt->execute(['c' => $job->contentId, 's' => $job->status]);

            return new TranscodeJob((int) $stmt->fetchColumn(), $job->contentId, $job->status, $job->error);
        }

        $finished = in_array($job->status, [TranscodeJob::DONE, TranscodeJob::FAILED], true);
        $stmt = $this->pdo->prepare(
            'UPDATE transcode_jobs
             SET status = :s, error = :e, finished_at = CASE WHEN :f THEN now() ELSE NULL END
             WHERE id = :id'
        );
        $stmt->bindValue('s', $job->status);
        $stmt->bindValue('e', $job->error);
        $stmt->bindValue('f', $finished, \PDO::PARAM_BOOL);
        $stmt->bindValue('id', $job->id, \PDO::PARAM_INT);
        $stmt->execute();

        return $job;
    }

    public function claimNext(): ?TranscodeJob
    {
        $this->pdo->beginTransaction();
        try {
            // SKIP LOCKED позволяет нескольким воркерам брать разные задачи без блокировок друг на друге
            $row = $this->pdo->query(
                "SELECT id, content_id FROM transcode_jobs WHERE status = 'pending'
                 ORDER BY id LIMIT 1 FOR UPDATE SKIP LOCKED"
            )->fetch();

            if (!$row) {
                $this->pdo->commit();
                return null;
            }

            $this->pdo->prepare("UPDATE transcode_jobs SET status = 'running' WHERE id = :id")
                ->execute(['id' => $row['id']]);
            $this->pdo->commit();

            return new TranscodeJob((int) $row['id'], (int) $row['content_id'], TranscodeJob::RUNNING);
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
}
