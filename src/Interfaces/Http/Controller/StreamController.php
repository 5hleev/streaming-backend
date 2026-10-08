<?php
declare(strict_types=1);

namespace App\Interfaces\Http\Controller;

use App\Application\Port\MediaStorage;
use App\Application\Port\UrlSigner;
use App\Application\UseCase\RequestStream;
use App\Domain\Exception\AccessDeniedException;
use App\Domain\Exception\NotFoundException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class StreamController extends BaseController
{
    public function __construct(
        private readonly RequestStream $requestStream,
        private readonly MediaStorage $storage,
        private readonly UrlSigner $signer,
        private readonly StreamFactoryInterface $streams,
    ) {
    }

    /** Этап 1: авторизованный пользователь получает подписанный URL манифеста. */
    public function request(ServerRequestInterface $req, ResponseInterface $res, array $args): ResponseInterface
    {
        $r = $this->requestStream->execute((int) $req->getAttribute('userId'), (int) $args['id']);
        $url = sprintf('/stream/%d/%s?exp=%d&sig=%s', $r['contentId'], $r['file'], $r['exp'], $r['sig']);

        return $this->json($res, ['url' => $url, 'expires_at' => $r['exp']]);
    }

    /** Этап 2: плеер забирает манифест и сегменты; доступ проверяется по подписи, а не по JWT. */
    public function serve(ServerRequestInterface $req, ResponseInterface $res, array $args): ResponseInterface
    {
        $contentId = (int) $args['id'];
        $file = (string) $args['file'];
        $q = $req->getQueryParams();
        $exp = (int) ($q['exp'] ?? 0);
        $sig = (string) ($q['sig'] ?? '');

        if (!$this->signer->verify(RequestStream::resource($contentId), $exp, $sig)) {
            throw new AccessDeniedException('Ссылка недействительна или истекла');
        }
        // белый список имён защищает от выхода за пределы каталога (../)
        if (!preg_match('/^[A-Za-z0-9_\-]+\.(m3u8|ts)$/', $file)) {
            throw new NotFoundException('Файл не найден');
        }
        $path = $this->storage->hlsFile($contentId, $file) ?? throw new NotFoundException('Файл не найден');

        if (str_ends_with($file, '.m3u8')) {
            return $this->manifest($res, $path, $exp, $sig);
        }

        return $res
            ->withHeader('Content-Type', 'video/mp2t')
            ->withHeader('Content-Length', (string) filesize($path))
            ->withHeader('Cache-Control', 'private, max-age=60')
            ->withBody($this->streams->createStreamFromFile($path, 'rb'));
    }

    /** Дописывает подпись к ссылкам на сегменты, иначе плеер запросит их без параметров доступа. */
    private function manifest(ResponseInterface $res, string $path, int $exp, string $sig): ResponseInterface
    {
        $suffix = '?exp=' . $exp . '&sig=' . $sig;
        $lines = array_map(
            static fn (string $l) => ($l !== '' && $l[0] !== '#') ? $l . $suffix : $l,
            preg_split('/\r\n|\r|\n/', (string) file_get_contents($path))
        );
        $res->getBody()->write(implode("\n", $lines));

        return $res->withHeader('Content-Type', 'application/vnd.apple.mpegurl')->withHeader('Cache-Control', 'no-store');
    }
}
