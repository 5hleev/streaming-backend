<?php
declare(strict_types=1);

namespace App\Interfaces\Http\Controller;

use App\Application\UseCase\AttachMedia;
use App\Application\UseCase\CreateContent;
use App\Application\UseCase\ListContent;
use App\Domain\Entity\Content;
use App\Domain\Exception\ValidationException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ContentController extends BaseController
{
    public function __construct(
        private readonly ListContent $list,
        private readonly CreateContent $create,
        private readonly AttachMedia $attach,
    ) {
    }

    public function list(ServerRequestInterface $req, ResponseInterface $res): ResponseInterface
    {
        $q = $req->getQueryParams();
        $items = $this->list->execute(
            isset($q['q']) ? (string) $q['q'] : null,
            isset($q['genre']) ? (string) $q['genre'] : null,
            (int) ($q['page'] ?? 1),
            (int) ($q['per_page'] ?? 20),
        );

        return $this->json($res, ['items' => array_map($this->present(...), $items)]);
    }

    public function create(ServerRequestInterface $req, ResponseInterface $res): ResponseInterface
    {
        $b = $this->body($req);
        $content = $this->create->execute(
            (string) ($b['title'] ?? ''),
            (string) ($b['description'] ?? ''),
            (string) ($b['genre'] ?? ''),
            (int) ($b['release_year'] ?? 0),
            filter_var($b['is_free'] ?? false, FILTER_VALIDATE_BOOLEAN),
        );

        return $this->json($res, $this->present($content), 201);
    }

    /** @param array{id:string} $args */
    public function uploadMedia(ServerRequestInterface $req, ResponseInterface $res, array $args): ResponseInterface
    {
        $file = $req->getUploadedFiles()['file'] ?? null;
        if ($file === null || $file->getError() !== UPLOAD_ERR_OK) {
            throw new ValidationException('Файл не передан или загружен с ошибкой (поле «file»)');
        }

        $tmp = tempnam(sys_get_temp_dir(), 'upl_');
        $file->moveTo($tmp);
        try {
            $job = $this->attach->execute((int) $args['id'], $tmp, (string) $file->getClientFilename());
        } finally {
            if (is_file($tmp)) {
                unlink($tmp); // при ошибке проверки временный файл не должен оставаться на диске
            }
        }

        return $this->json($res, ['job_id' => $job->id, 'status' => $job->status], 202);
    }

    /** @return array<string,mixed> */
    private function present(Content $c): array
    {
        return [
            'id' => $c->id,
            'title' => $c->title,
            'description' => $c->description,
            'genre' => $c->genre,
            'release_year' => $c->releaseYear,
            'is_free' => $c->isFree,
            'status' => $c->status,
        ];
    }
}
