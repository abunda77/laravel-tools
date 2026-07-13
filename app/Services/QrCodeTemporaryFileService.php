<?php

namespace App\Services;

use BaconQrCode\Renderer\Color\Rgb;
use BaconQrCode\Renderer\GDLibRenderer;
use BaconQrCode\Renderer\RendererStyle\Fill;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Storage;
use Ramsey\Uuid\Uuid;
use RuntimeException;

class QrCodeTemporaryFileService
{
    public const Disk = 'local';

    public const Directory = 'qr-codes-tmp';

    public const ExpiryHours = 24;

    public const Size = 800;

    public const Margin = 4;

    public const PngQuality = 9;

    public const JpgQuality = 90;

    public function disk(): string
    {
        return self::Disk;
    }

    /**
     * @return array{png_filename: string, jpg_filename: string, preview_data_uri: string}
     */
    public function generate(string $content): array
    {
        $this->cleanupExpiredFiles();

        $token = Uuid::uuid4()->toString();
        $pngFilename = $token.'.png';
        $jpgFilename = $token.'.jpg';

        $storage = Storage::disk(self::Disk);
        $storage->makeDirectory(self::Directory);

        $pngContent = $this->renderImage($content, 'png', self::PngQuality);
        $jpgContent = $this->renderImage($content, 'jpg', self::JpgQuality);

        $storage->put(self::Directory.'/'.$pngFilename, $pngContent);
        $storage->put(self::Directory.'/'.$jpgFilename, $jpgContent);

        return [
            'png_filename' => $pngFilename,
            'jpg_filename' => $jpgFilename,
            'preview_data_uri' => 'data:image/png;base64,'.base64_encode($pngContent),
        ];
    }

    public function delete(?string $filename): void
    {
        if (blank($filename)) {
            return;
        }

        if (! $this->isValidFilename($filename)) {
            return;
        }

        Storage::disk(self::Disk)->delete(self::Directory.'/'.$filename);
    }

    /**
     * @param  array<int, string|null>  $filenames
     */
    public function deleteMany(array $filenames): void
    {
        foreach ($filenames as $filename) {
            $this->delete($filename);
        }
    }

    public function cleanupExpiredFiles(): int
    {
        $storage = Storage::disk(self::Disk);

        if (! $storage->exists(self::Directory)) {
            return 0;
        }

        $threshold = now()->subHours(self::ExpiryHours)->getTimestamp();
        $deleted = 0;

        foreach ($storage->files(self::Directory) as $file) {
            $basename = basename($file);

            if (! $this->isValidFilename($basename)) {
                continue;
            }

            if ($storage->lastModified($file) < $threshold) {
                $storage->delete($file);
                $deleted++;
            }
        }

        return $deleted;
    }

    public function path(string $filename): string
    {
        if (! $this->isValidFilename($filename)) {
            throw new RuntimeException('Nama file QR tidak valid: '.$filename);
        }

        return self::Directory.'/'.$filename;
    }

    public function mimeType(string $filename): string
    {
        return str_ends_with(strtolower($filename), '.png') ? 'image/png' : 'image/jpeg';
    }

    private function isValidFilename(string $filename): bool
    {
        return (bool) preg_match('/\A[0-9a-f-]+\.(png|jpg)\z/i', $filename);
    }

    /**
     * @return non-empty-string
     */
    private function renderImage(string $content, string $format, int $quality): string
    {
        $fill = Fill::uniformColor(
            new Rgb(255, 255, 255),
            new Rgb(0, 0, 0),
        );

        $renderer = new GDLibRenderer(
            size: self::Size,
            margin: self::Margin,
            imageFormat: $format,
            compressionQuality: $quality,
            fill: $fill,
        );

        return (new Writer($renderer))->writeString($content);
    }
}
