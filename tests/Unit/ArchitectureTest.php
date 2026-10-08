<?php
declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Автоматическая проверка правила зависимостей чистой архитектуры:
 * исходный код может зависеть только от своего или внутреннего слоя.
 */
final class ArchitectureTest extends TestCase
{
    /** Чем меньше число, тем «внутреннее» слой. */
    private const LAYERS = ['Domain' => 0, 'Application' => 1, 'Interfaces' => 2, 'Infrastructure' => 3];

    /** @return array<string,string> путь => содержимое */
    private function sources(): array
    {
        $root = realpath(__DIR__ . '/../../src');
        $this->assertNotFalse($root, 'Каталог src не найден');
        $files = [];
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS));
        foreach ($it as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[substr($file->getPathname(), strlen($root) + 1)] = (string) file_get_contents($file->getPathname());
            }
        }
        ksort($files);
        $this->assertNotEmpty($files);

        return $files;
    }

    private function layerOf(string $relativePath): string
    {
        return explode(DIRECTORY_SEPARATOR, $relativePath)[0];
    }

    public function testDependenciesPointInward(): void
    {
        $violations = [];
        foreach ($this->sources() as $path => $code) {
            $layer = $this->layerOf($path);
            preg_match_all('/^use App\\\\(\w+)\\\\/m', $code, $uses);
            preg_match_all('/\\\\App\\\\(\w+)\\\\/', $code, $inline);
            foreach (array_merge($uses[1], $inline[1]) as $target) {
                if ((self::LAYERS[$target] ?? 0) > self::LAYERS[$layer]) {
                    $violations[] = "$path: слой $layer зависит от внешнего слоя $target";
                }
            }
        }
        $this->assertSame([], $violations, implode("\n", $violations));
    }

    public function testNamespacesMatchDirectories(): void
    {
        $violations = [];
        foreach ($this->sources() as $path => $code) {
            preg_match('/^namespace (.+);/m', $code, $m);
            $expected = 'App\\' . str_replace(DIRECTORY_SEPARATOR, '\\', dirname($path));
            if (($m[1] ?? '') !== $expected) {
                $violations[] = "$path: пространство имён {$m[1]}, ожидалось $expected";
            }
        }
        $this->assertSame([], $violations, implode("\n", $violations));
    }

    public function testInnerLayersAreFreeOfFrameworksAndDatabase(): void
    {
        $violations = [];
        foreach ($this->sources() as $path => $code) {
            $layer = $this->layerOf($path);
            if (in_array($layer, ['Domain', 'Application'], true)) {
                // внутренние слои не знают ни о фреймворках, ни о PSR-интерфейсах HTTP, ни о PDO
                if (preg_match('/^use (Slim|Psr|Firebase|DI)\\\\/m', $code) || preg_match('/\bPDO\b/', $code)) {
                    $violations[] = "$path: внутренний слой $layer обращается к фреймворку или PDO";
                }
            }
            if ($layer === 'Interfaces' && preg_match('/^use (Slim|Firebase|DI)\\\\/m', $code)) {
                $violations[] = "$path: слой Interfaces зависит от фреймворка";
            }
        }
        $this->assertSame([], $violations, implode("\n", $violations));
    }
}
