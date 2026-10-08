<?php
declare(strict_types=1);

namespace App\Interfaces\Persistence;

use App\Domain\Entity\Content;
use App\Domain\Repository\ContentRepository;

final class PdoContentRepository implements ContentRepository
{
    public function __construct(private readonly \PDO $pdo)
    {
    }

    public function save(Content $content): Content
    {
        $params = [
            'title' => $content->title,
            'description' => $content->description,
            'genre' => $content->genre,
            'year' => $content->releaseYear,
            'free' => $content->isFree ? 'true' : 'false',
            'status' => $content->status,
            'source' => $content->sourcePath,
            'hls' => $content->hlsPath,
        ];

        if ($content->id === null) {
            $stmt = $this->pdo->prepare(
                'INSERT INTO contents (title, description, genre, release_year, is_free, status, source_path, hls_path)
                 VALUES (:title, :description, :genre, :year, :free, :status, :source, :hls) RETURNING id'
            );
            $stmt->execute($params);

            return $content->withId((int) $stmt->fetchColumn());
        }

        $stmt = $this->pdo->prepare(
            'UPDATE contents SET title = :title, description = :description, genre = :genre, release_year = :year,
                    is_free = :free, status = :status, source_path = :source, hls_path = :hls
             WHERE id = :id'
        );
        $stmt->execute($params + ['id' => $content->id]);

        return $content;
    }

    public function findById(int $id): ?Content
    {
        $stmt = $this->pdo->prepare('SELECT * FROM contents WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    public function searchPublished(?string $query, ?string $genre, int $limit, int $offset): array
    {
        $sql = "SELECT * FROM contents WHERE status = 'published'";
        $params = [];
        if ($query !== null) {
            $sql .= ' AND title ILIKE :q';
            // экранируем служебные символы LIKE, чтобы поиск работал как подстрока
            $params['q'] = '%' . addcslashes($query, '%_\\') . '%';
        }
        if ($genre !== null) {
            $sql .= ' AND lower(genre) = lower(:genre)';
            $params['genre'] = $genre;
        }
        $sql .= ' ORDER BY id DESC LIMIT :limit OFFSET :offset';

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue('limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();

        return array_map($this->hydrate(...), $stmt->fetchAll());
    }

    /** @param array<string,mixed> $row */
    private function hydrate(array $row): Content
    {
        return new Content(
            (int) $row['id'],
            $row['title'],
            $row['description'],
            $row['genre'],
            (int) $row['release_year'],
            (bool) $row['is_free'],
            $row['status'],
            $row['source_path'],
            $row['hls_path'],
        );
    }
}
