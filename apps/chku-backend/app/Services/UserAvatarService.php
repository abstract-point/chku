<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

final class UserAvatarService
{
    private const SIZE = 256;
    private const JPEG_QUALITY = 85;

    public function store(User $user, UploadedFile $file): string
    {
        $sourcePath = $file->getRealPath();

        if (! $sourcePath) {
            throw new RuntimeException('Не удалось прочитать файл аватара.');
        }

        $source = $this->createImage($sourcePath, (string) $file->getMimeType());
        $width = \imagesx($source);
        $height = \imagesy($source);
        $cropSize = min($width, $height);
        $srcX = (int) floor(($width - $cropSize) / 2);
        $srcY = (int) floor(($height - $cropSize) / 2);

        $target = \imagecreatetruecolor(self::SIZE, self::SIZE);
        \imagecopyresampled(
            $target,
            $source,
            0,
            0,
            $srcX,
            $srcY,
            self::SIZE,
            self::SIZE,
            $cropSize,
            $cropSize,
        );

        ob_start();
        \imagejpeg($target, null, self::JPEG_QUALITY);
        $contents = \ob_get_clean();

        \imagedestroy($source);
        \imagedestroy($target);

        if (! is_string($contents)) {
            throw new RuntimeException('Не удалось обработать аватар.');
        }

        $path = "avatars/users/{$user->id}.jpg";
        Storage::disk('public')->put($path, $contents);

        return $path;
    }

    private function createImage(string $path, string $mimeType): \GdImage
    {
        $image = match ($mimeType) {
            'image/jpeg' => \imagecreatefromjpeg($path),
            'image/png' => \imagecreatefrompng($path),
            'image/webp' => \imagecreatefromwebp($path),
            default => false,
        };

        if (! $image instanceof \GdImage) {
            throw new RuntimeException('Неподдерживаемый формат аватара.');
        }

        return $image;
    }
}
