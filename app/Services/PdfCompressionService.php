<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

/**
 * #185 Сжатие PDF-документов конкурсной документации при загрузке.
 *
 * Использует Ghostscript, если он установлен в окружении. Сжатие «мягкое»
 * (/ebook — 150 dpi для растровых изображений; векторный текст и схемы остаются
 * читаемыми). Если Ghostscript недоступен, сжатый файл невалиден или получился
 * не меньше исходного — оставляем оригинал без изменений (graceful fallback).
 */
class PdfCompressionService
{
    /**
     * Сжать PDF «на месте» (перезаписать файл по пути $path).
     * Безопасно: при любой ошибке исходный файл остаётся нетронутым.
     */
    public function compressInPlace(string $path): void
    {
        $binary = $this->binary();

        if ($binary === null || ! is_file($path)) {
            return;
        }

        $originalSize = filesize($path);
        if ($originalSize === false || $originalSize === 0) {
            return;
        }

        $tmp = $path.'.compressed.pdf';

        try {
            $process = new Process([
                $binary,
                '-sDEVICE=pdfwrite',
                '-dCompatibilityLevel=1.4',
                '-dPDFSETTINGS=/ebook',
                '-dNOPAUSE',
                '-dQUIET',
                '-dBATCH',
                '-sOutputFile='.$tmp,
                $path,
            ]);
            $process->setTimeout(120);
            $process->run();

            $compressedSize = is_file($tmp) ? filesize($tmp) : false;

            // Принимаем сжатие только если оно успешно и реально уменьшило файл.
            if ($process->isSuccessful() && $compressedSize && $compressedSize < $originalSize) {
                rename($tmp, $path);
                Log::info("PDF сжат: {$path} ({$originalSize} → {$compressedSize} байт).");
            } elseif (is_file($tmp)) {
                @unlink($tmp);
            }
        } catch (\Throwable $e) {
            if (is_file($tmp)) {
                @unlink($tmp);
            }
            Log::warning("Сжатие PDF не удалось ({$path}): ".$e->getMessage());
        }
    }

    /**
     * Доступен ли Ghostscript в окружении.
     */
    public function isAvailable(): bool
    {
        return $this->binary() !== null;
    }

    /**
     * Путь/имя бинарника Ghostscript (кэшируется на процесс). null — если не найден.
     */
    private function binary(): ?string
    {
        static $resolved = false;
        static $binary = null;

        if ($resolved) {
            return $binary;
        }

        $resolved = true;
        $locator = PHP_OS_FAMILY === 'Windows' ? 'where' : 'command -v';

        foreach (['gs', 'gswin64c', 'gswin32c'] as $candidate) {
            $output = @shell_exec($locator.' '.$candidate.' 2>&1');
            if (is_string($output) && trim($output) !== '' && ! str_contains(strtolower($output), 'not found')) {
                return $binary = $candidate;
            }
        }

        return $binary = null;
    }
}
