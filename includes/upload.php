<?php

declare(strict_types=1);

function saveLocationImage(array $file): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        jsonResponse(['error' => 'Image upload failed'], 400);
    }

    if (($file['size'] ?? 0) > 5 * 1024 * 1024) {
        jsonResponse(['error' => 'Image too large (max 5 MB)'], 400);
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
    ];

    if (!isset($allowed[$mime])) {
        jsonResponse(['error' => 'Invalid image type. Use JPG, PNG, WebP or GIF.'], 400);
    }

    $dir = ROOT_PATH . '/uploads/locations';
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
        jsonResponse(['error' => 'Upload directory not writable'], 500);
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
    $dest = $dir . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        jsonResponse(['error' => 'Could not save image'], 500);
    }

    return 'uploads/locations/' . $filename;
}
