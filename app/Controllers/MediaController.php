<?php

declare(strict_types=1);

namespace App\Controllers;

use CodeIgniter\Exceptions\PageNotFoundException;

/**
 * Streams files from `writable/uploads/`.
 *
 * Uploads are kept outside the web root on purpose, so `public/` can never be
 * asked for a user-supplied file and a disguised script is not reachable as a
 * static asset. This controller is the only way in, and it:
 *
 * - resolves the path through `UploadService::absolutePath()`, which rejects
 *   anything that escapes the uploads directory;
 * - sets `Content-Disposition: inline` with an explicit content type, so a
 *   browser renders an image instead of trying to execute anything;
 * - sends `X-Content-Type-Options: nosniff` so a wrong guess cannot be
 *   reinterpreted.
 */
class MediaController extends BaseController
{
    /**
     * Extension to MIME type. Only the types `UploadService` writes are served;
     * anything else is refused rather than guessed at.
     *
     * @var array<string, string>
     */
    private const MIME_TYPES = [
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'webp' => 'image/webp',
        'gif'  => 'image/gif',
        'mp4'  => 'video/mp4',
        'webm' => 'video/webm',
    ];

    public function show(string $path)
    {
        $absolute = $this->uploadService->absolutePath($path);

        if ($absolute === null || ! is_file($absolute)) {
            throw PageNotFoundException::forPageNotFound();
        }

        $extension = strtolower(pathinfo($absolute, PATHINFO_EXTENSION));
        $mime      = self::MIME_TYPES[$extension] ?? null;

        if ($mime === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $this->response
            ->setHeader('Content-Type', $mime)
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setHeader('Content-Disposition', 'inline; filename="' . basename($absolute) . '"')
            ->setBody(file_get_contents($absolute) ?: '');
    }
}
