<?php

declare(strict_types=1);

namespace App\Services;

use CodeIgniter\Files\File;
use CodeIgniter\Files\FileCollection;
use CodeIgniter\HTTP\Files\UploadedFileInterface;

/**
 * Stores seller and customer uploads under `writable/uploads/`.
 *
 * Uploads deliberately live outside the web root. `public/` is therefore free of
 * user-supplied files, so a malicious image can never be requested directly as
 * `/uploads/x.php`; reads go through `MediaController` instead.
 *
 * Two further rules from AGENTS.md are enforced here:
 *
 * - the stored name is always generated, so a client can never choose a path or
 *   a double extension such as `logo.php.jpg`;
 * - the extension is derived from the *detected* MIME type, not from the
 *   filename the browser sent.
 */
class UploadService
{
    /**
     * Image types the platform accepts, mapped to the extension that will be
     * written. Adding a video entry is the only change needed to support
     * seller story clips.
     *
     * @var array<string, string>
     */
    private const ALLOWED = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
        'video/mp4'  => 'mp4',
        'video/webm' => 'webm',
    ];

    /**
     * @var array<string, array{0: int, 1: int}>
     */
    private const LIMITS = [
        'image' => [2 * 1024 * 1024, 4096 * 4096],
        'video' => [25 * 1024 * 1024, 0],
    ];

    /**
     * Store one uploaded file and return its relative path, e.g.
     * `products/2026/10/a1b2c3d4.jpg`.
     *
     * @param UploadedFileInterface $file    The uploaded file.
     * @param string               $context Sub-directory under uploads/.
     * @param string               $kind    Either `image` or `video`.
     *
     * @return array{path: string, mime: string, size: int}|null Null when the
     *                                                            upload did not validate.
     */
    public function store(UploadedFileInterface $file, string $context, string $kind = 'image'): ?array
    {
        if (! $file->isValid() || $file->getError() !== UPLOAD_ERR_OK) {
            return null;
        }

        if ($file->getSize() === 0) {
            return null;
        }

        [$maxBytes, $maxDimension] = self::LIMITS[$kind] ?? self::LIMITS['image'];

        if ($file->getSize() > $maxBytes) {
            return null;
        }

        // `getMimeType()` reads the file's magic bytes, so a renamed `.php` is
        // recognised for what it is and rejected.
        $mime = (string) $file->getMimeType();

        if (! isset(self::ALLOWED[$mime])) {
            return null;
        }

        if ($kind === 'image' && $maxDimension > 0 && ! $this->dimensionsAreSane($file)) {
            return null;
        }

        $relativeDir = trim($context, '/') . '/' . date('Y/m');
        $absoluteDir = WRITEPATH . 'uploads/' . $relativeDir;

        if (! is_dir($absoluteDir) && ! mkdir($absoluteDir, 0775, true) && ! is_dir($absoluteDir)) {
            return null;
        }

        // A random basename is what stops a client from steering the filename.
        $name     = bin2hex(random_bytes(16)) . '.' . self::ALLOWED[$mime];
        $relative = $relativeDir . '/' . $name;

        $file->move($absoluteDir, $name);

        return [
            'path' => $relative,
            'mime' => $mime,
            'size' => (int) $file->getSize(),
        ];
    }

    /**
     * Store every valid file from a multi-file input.
     *
     * @param list<UploadedFileInterface> $files
     *
     * @return list<array{path: string, mime: string, size: int}>
     */
    public function storeAll(FileCollection $files, string $context, string $kind = 'image'): array
    {
        $stored = [];

        foreach ($files as $file) {
            $result = $this->store($file, $context, $kind);

            if ($result !== null) {
                $stored[] = $result;
            }
        }

        return $stored;
    }

    /**
     * Absolute path for a stored relative path, or null when it would escape
     * the uploads directory.
     */
    public function absolutePath(string $relative): ?string
    {
        $relative = ltrim(str_replace('\\', '/', $relative), '/');

        if ($relative === '' || str_contains($relative, '..')) {
            return null;
        }

        $base = realpath(WRITEPATH . 'uploads');
        $full = realpath(WRITEPATH . 'uploads/' . $relative);

        // realpath() returns false for a missing file; the prefix check is what
        // actually guarantees containment.
        if ($base === false || $full === false || ! str_starts_with($full, $base . DIRECTORY_SEPARATOR)) {
            return null;
        }

        return $full;
    }

    /**
     * Delete a stored file, ignoring a path that is not ours.
     */
    public function delete(?string $relative): void
    {
        if ($relative === null || $relative === '') {
            return;
        }

        $absolute = $this->absolutePath($relative);

        if ($absolute !== null && is_file($absolute)) {
            @unlink($absolute);
        }
    }

    /**
     * Reject an image whose pixel dimensions are absurd.
     *
     * A decompression bomb is small on disk and enormous in memory, so the size
     * cap alone is not enough.
     */
    private function dimensionsAreSane(UploadedFileInterface $file): bool
    {
        [, $maxDimension] = self::LIMITS['image'];

        $size = @getimagesize($file->getTempName());

        if ($size === false) {
            return false;
        }

        return $size[0] <= $maxDimension && $size[1] <= $maxDimension;
    }
}
