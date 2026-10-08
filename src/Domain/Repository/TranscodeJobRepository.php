<?php
declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\TranscodeJob;

interface TranscodeJobRepository
{
    public function save(TranscodeJob $job): TranscodeJob;

    /** Атомарно берёт следующую задачу в статусе pending и переводит её в running. */
    public function claimNext(): ?TranscodeJob;
}
