<?php
/**
 * Kastana Jobs — Secure image upload handling
 * Validates by real content (getimagesize + finfo), not the client's filename.
 */

const THUMB_MAX_DIM = 160;

/**
 * Create a resized copy of an uploaded image for use as a small thumbnail.
 * Returns null (caller should fall back to the original) if GD is unavailable,
 * the image is already small enough, or the resize fails for any reason.
 */
function make_thumbnail(string $srcPath, string $mime): ?string
{
    if (!extension_loaded('gd')) return null;
    $dims = @getimagesize($srcPath);
    if (!$dims) return null;
    [$w, $h] = $dims;
    if ($w <= THUMB_MAX_DIM && $h <= THUMB_MAX_DIM) return null;

    $ratio = min(THUMB_MAX_DIM / $w, THUMB_MAX_DIM / $h);
    $newW = max(1, (int) round($w * $ratio));
    $newH = max(1, (int) round($h * $ratio));

    $src = match ($mime) {
        'image/jpeg' => @imagecreatefromjpeg($srcPath),
        'image/png'  => @imagecreatefrompng($srcPath),
        'image/webp' => @imagecreatefromwebp($srcPath),
        'image/gif'  => @imagecreatefromgif($srcPath),
        default => null,
    };
    if (!$src) return null;

    $dst = imagecreatetruecolor($newW, $newH);
    if (in_array($mime, ['image/png', 'image/gif', 'image/webp'], true)) {
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
        imagefilledrectangle($dst, 0, 0, $newW, $newH, $transparent);
    }
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $w, $h);

    $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'][$mime];
    $thumbName = pathinfo($srcPath, PATHINFO_FILENAME) . '_thumb.' . $ext;
    $thumbPath = dirname($srcPath) . '/' . $thumbName;

    $ok = match ($mime) {
        'image/jpeg' => imagejpeg($dst, $thumbPath, 82),
        'image/png'  => imagepng($dst, $thumbPath, 6),
        'image/webp' => imagewebp($dst, $thumbPath, 82),
        'image/gif'  => imagegif($dst, $thumbPath),
        default => false,
    };
    imagedestroy($src);
    imagedestroy($dst);
    if (!$ok) return null;
    @chmod($thumbPath, 0644);
    return 'uploads/' . $thumbName;
}

/**
 * Process an uploaded image field.
 * @return array{path: ?string, thumb_path: ?string, error: ?string}
 *   path  = relative path (e.g. "uploads/job_xxx.jpg") on success, or null if none uploaded
 *   thumb_path = relative path to a smaller resized copy, or null if none was generated
 *   error = a user-facing message on failure, or null
 */
function save_uploaded_image(string $field): array
{
    // No file chosen is a valid (optional) case.
    if (empty($_FILES[$field]) || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['path' => null, 'thumb_path' => null, 'error' => null];
    }

    $f = $_FILES[$field];

    if ($f['error'] !== UPLOAD_ERR_OK) {
        // e.g. exceeded php.ini limits, partial upload, etc.
        return ['path' => null, 'thumb_path' => null, 'error' => t('err_upload')];
    }
    if (!is_uploaded_file($f['tmp_name'])) {
        return ['path' => null, 'thumb_path' => null, 'error' => t('err_upload')];
    }
    if ($f['size'] <= 0 || $f['size'] > MAX_UPLOAD_BYTES) {
        return ['path' => null, 'thumb_path' => null, 'error' => t('err_upload_size')];
    }

    // Content-based validation (ignore the browser-supplied type & name entirely).
    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
    ];

    $info = @getimagesize($f['tmp_name']);
    if ($info === false || empty($info['mime'])) {
        return ['path' => null, 'thumb_path' => null, 'error' => t('err_upload_type')];
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $realMime = finfo_file($finfo, $f['tmp_name']);
    finfo_close($finfo);

    // Both checks must agree and be on the allow-list.
    if ($realMime !== $info['mime'] || !isset($allowed[$realMime])) {
        return ['path' => null, 'thumb_path' => null, 'error' => t('err_upload_type')];
    }
    $ext = $allowed[$realMime];

    // Random, unguessable filename — never derived from user input.
    $name = 'job_' . date('Ymd') . '_' . bin2hex(random_bytes(10)) . '.' . $ext;

    $destDir = dirname(__DIR__) . '/uploads';
    if (!is_dir($destDir) && !@mkdir($destDir, 0775, true)) {
        error_log('uploads dir missing and could not be created: ' . $destDir);
        return ['path' => null, 'thumb_path' => null, 'error' => t('err_upload')];
    }
    $dest = $destDir . '/' . $name;

    if (!move_uploaded_file($f['tmp_name'], $dest)) {
        return ['path' => null, 'thumb_path' => null, 'error' => t('err_upload')];
    }
    @chmod($dest, 0644);

    $thumbPath = make_thumbnail($dest, $realMime);

    return ['path' => 'uploads/' . $name, 'thumb_path' => $thumbPath, 'error' => null];
}

/** Safely delete a previously stored image (only within uploads/). */
function delete_uploaded_image(?string $relPath): void
{
    if (!$relPath) return;
    // Guard against path traversal — only allow our own uploads/ files.
    if (!preg_match('#^uploads/[A-Za-z0-9._-]+$#', $relPath)) return;
    $full = dirname(__DIR__) . '/' . $relPath;
    if (is_file($full)) @unlink($full);
}
