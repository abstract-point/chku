<?php

namespace App\Services;

final class BookCoverImageService
{
    private const MAX_FULL_WIDTH = 800;
    private const MAX_FULL_HEIGHT = 1200;
    private const MAX_THUMB_WIDTH = 200;
    private const MAX_THUMB_HEIGHT = 300;
    private const JPEG_QUALITY = 85;

    public function processFile(string $sourcePath): array
    {
        $imageInfo = getimagesize($sourcePath);
        if ($imageInfo === false) {
            throw new \RuntimeException('Unable to read image: '.$sourcePath);
        }

        [$originalWidth, $originalHeight, $type] = $imageInfo;

        $srcImage = $this->createImageFromFile($sourcePath, $type);
        if ($srcImage === false) {
            throw new \RuntimeException('Unable to create image resource from: '.$sourcePath);
        }

        $full = $this->resize($srcImage, $originalWidth, $originalHeight, self::MAX_FULL_WIDTH, self::MAX_FULL_HEIGHT);
        $thumb = $this->resize($srcImage, $originalWidth, $originalHeight, self::MAX_THUMB_WIDTH, self::MAX_THUMB_HEIGHT);

        imagedestroy($srcImage);

        $fullContent = $this->jpegToBinary($full['resource'], self::JPEG_QUALITY);
        $thumbContent = $this->jpegToBinary($thumb['resource'], self::JPEG_QUALITY);

        imagedestroy($full['resource']);
        imagedestroy($thumb['resource']);

        return [
            'full' => [
                'content' => $fullContent,
                'width' => $full['width'],
                'height' => $full['height'],
                'size' => strlen($fullContent),
                'mime' => 'image/jpeg',
            ],
            'thumbnail' => [
                'content' => $thumbContent,
                'width' => $thumb['width'],
                'height' => $thumb['height'],
                'size' => strlen($thumbContent),
                'mime' => 'image/jpeg',
            ],
        ];
    }

    private function resize(\GdImage $srcImage, int $srcW, int $srcH, int $maxW, int $maxH): array
    {
        $ratio = min($maxW / $srcW, $maxH / $srcH, 1.0);

        $newW = (int) round($srcW * $ratio);
        $newH = (int) round($srcH * $ratio);

        $dstImage = imagecreatetruecolor($newW, $newH);
        imagecopyresampled($dstImage, $srcImage, 0, 0, 0, 0, $newW, $newH, $srcW, $srcH);

        return [
            'resource' => $dstImage,
            'width' => $newW,
            'height' => $newH,
        ];
    }

    private function createImageFromFile(string $path, int $type): \GdImage|false
    {
        return match ($type) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($path),
            IMAGETYPE_PNG => imagecreatefrompng($path),
            IMAGETYPE_GIF => imagecreatefromgif($path),
            IMAGETYPE_WEBP => imagecreatefromwebp($path),
            default => false,
        };
    }

    private function jpegToBinary(\GdImage $image, int $quality): string
    {
        ob_start();
        imagejpeg($image, null, $quality);
        return ob_get_clean();
    }
}
