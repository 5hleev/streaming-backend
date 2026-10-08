<?php
declare(strict_types=1);

namespace App\Application\UseCase;

use App\Domain\Entity\Content;
use App\Domain\Repository\ContentRepository;

final class CreateContent
{
    public function __construct(private readonly ContentRepository $contents)
    {
    }

    public function execute(string $title, string $description, string $genre, int $year, bool $isFree): Content
    {
        return $this->contents->save(new Content(null, $title, $description, trim($genre), $year, $isFree));
    }
}
