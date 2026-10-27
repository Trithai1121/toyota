<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use RuntimeException;

class ResponsiveImageProcessor
{
    private const WIDTHS = [480, 960, 1600];

    public function process(string $path): string
    {
        $disk = Storage::disk('public');

        if (! $disk->exists($path)) {
            throw new RuntimeException("Uploaded image [{$path}] is missing from public storage.");
        }

        $contents = $disk->get($path);
        $imageInfo = @getimagesizefromstring($contents);

        if (! $imageInfo || ! in_array($imageInfo['mime'] ?? null, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            throw new RuntimeException('Uploaded image is not a supported JPEG, PNG, or WebP file.');
        }

        [$sourceWidth, $sourceHeight] = $imageInfo;

        if ($sourceWidth < 1 || $sourceHeight < 1 || $sourceWidth * $sourceHeight > 40_000_000) {
            throw new RuntimeException('Uploaded image dimensions exceed the supported limit.');
        }

        // Image uploads must remain usable on hosts where GD was built without WebP.
        // Keep the original validated image as a fallback; the front end already
        // handles images without responsive variants.
        if (! function_exists('imagewebp')) {
            return $path;
        }

        $source = @imagecreatefromstring($contents);
        if ($source === false) {
            throw new RuntimeException('Uploaded image could not be decoded.');
        }

        $directory = dirname($path);
        $name = 'image-'.substr(hash('sha256', $contents), 0, 24);
        $widths = array_values(array_unique(array_map(
            fn (int $width): int => min($width, $sourceWidth),
            self::WIDTHS,
        )));
        $largestWebp = null;

        foreach ($widths as $width) {
            $height = max(1, (int) round($sourceHeight * ($width / $sourceWidth)));
            $canvas = imagecreatetruecolor($width, $height);

            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
            $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
            imagefill($canvas, 0, 0, $transparent);
            imagecopyresampled($canvas, $source, 0, 0, 0, 0, $width, $height, $sourceWidth, $sourceHeight);

            ob_start();
            $encoded = imagewebp($canvas, null, 82);
            $webp = ob_get_clean();
            imagedestroy($canvas);

            if (! $encoded || ! is_string($webp) || $webp === '') {
                imagedestroy($source);
                throw new RuntimeException('The image could not be converted to WebP.');
            }

            $variantPath = $directory.'/'.$name.'-'.$width.'.webp';
            if (! $disk->put($variantPath, $webp)) {
                imagedestroy($source);
                throw new RuntimeException("Could not save responsive image [{$variantPath}].");
            }

            $largestWebp = $webp;
        }

        imagedestroy($source);

        if (! is_string($largestWebp) || ! $disk->put($directory.'/'.$name.'.webp', $largestWebp)) {
            throw new RuntimeException('Could not save the optimized WebP image.');
        }

        $manifestPath = $directory.'/'.$name.'.json';
        if (! $disk->put($manifestPath, json_encode(['widths' => $widths], JSON_THROW_ON_ERROR))) {
            throw new RuntimeException("Could not save responsive image metadata [{$manifestPath}].");
        }

        $optimizedPath = $directory.'/'.$name.'.webp';
        if ($path !== $optimizedPath) {
            $disk->delete($path);
        }

        return $optimizedPath;
    }

    public function isProcessed(string $path): bool
    {
        return $this->manifest($path) !== null;
    }

    public function srcset(string $path): ?string
    {
        $disk = Storage::disk('public');
        $manifest = $this->manifest($path);
        if ($manifest === null) {
            return null;
        }

        $directory = dirname($path);
        $name = pathinfo($path, PATHINFO_FILENAME);
        $sources = [];

        foreach ($manifest['widths'] as $width) {
            $variantPath = $directory.'/'.$name.'-'.$width.'.webp';
            if ($disk->exists($variantPath)) {
                $sources[] = $disk->url($variantPath).' '.$width.'w';
            }
        }

        return $sources ? implode(', ', $sources) : null;
    }

    private function manifest(string $path): ?array
    {
        $disk = Storage::disk('public');
        if (strtolower(pathinfo($path, PATHINFO_EXTENSION)) !== 'webp' || ! $disk->exists($path)) {
            return null;
        }

        $manifestPath = dirname($path).'/'.pathinfo($path, PATHINFO_FILENAME).'.json';
        if (! $disk->exists($manifestPath)) {
            return null;
        }

        $manifest = json_decode($disk->get($manifestPath), true);
        if (! is_array($manifest) || ! isset($manifest['widths']) || ! is_array($manifest['widths'])) {
            return null;
        }

        return $manifest;
    }
}
