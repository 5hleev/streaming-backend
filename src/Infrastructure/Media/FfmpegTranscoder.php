<?php
declare(strict_types=1);

namespace App\Infrastructure\Media;

use App\Application\Port\Transcoder;

final class FfmpegTranscoder implements Transcoder
{
    public function __construct(private readonly string $binary = 'ffmpeg', private readonly int $segmentSeconds = 6)
    {
    }

    public function toHls(string $sourceFile, string $outputDir): void
    {
        // Аргументы передаются массивом, без shell — исключена инъекция через имя файла
        $cmd = [
            $this->binary, '-hide_banner', '-nostats', '-loglevel', 'error', '-y', '-i', $sourceFile,
            // yuv420p и чётные размеры нужны для совместимости с профилем main и большинством плееров
            '-vf', 'scale=trunc(iw/2)*2:trunc(ih/2)*2',
            '-pix_fmt', 'yuv420p',
            '-c:v', 'libx264', '-preset', 'veryfast', '-profile:v', 'main',
            // ключевой кадр на границе каждого сегмента, иначе сегменты получаются длиннее hls_time
            '-force_key_frames', 'expr:gte(t,n_forced*' . $this->segmentSeconds . ')',
            '-c:a', 'aac', '-b:a', '128k',
            '-f', 'hls',
            '-hls_time', (string) $this->segmentSeconds,
            '-hls_playlist_type', 'vod',
            '-hls_segment_filename', $outputDir . '/seg_%05d.ts',
            $outputDir . '/master.m3u8',
        ];

        $process = proc_open($cmd, [1 => ['file', '/dev/null', 'w'], 2 => ['pipe', 'w']], $pipes);
        if (!is_resource($process)) {
            throw new \RuntimeException('Не удалось запустить ffmpeg');
        }
        // stdout не используется; stderr читается целиком, чтобы заполненный буфер канала не блокировал ffmpeg
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        if (proc_close($process) !== 0) {
            throw new \RuntimeException('ffmpeg завершился с ошибкой: ' . mb_substr((string) $stderr, -500));
        }
    }
}
