<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../main.php';
require_once __DIR__ . '/learning_lib.php';

function learning_image_json(int $status, array $payload): void
{
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

function learning_image_clean_filename(string $name): string
{
    $name = trim($name);
    $name = str_replace(["\0", "\r", "\n"], '', $name);
    $name = preg_replace('/\s+/', '-', $name);
    $name = preg_replace('/[^A-Za-z0-9._-]/', '', $name);
    $name = preg_replace('/-+/', '-', $name);
    $name = trim((string)$name, '-_.');
    return $name !== '' ? $name : 'course-image';
}

function learning_image_ensure_dir(string $path): void
{
    if (!is_dir($path) && !mkdir($path, 0755, true) && !is_dir($path)) {
        throw new RuntimeException('Could not create upload directory.');
    }
    if (!is_writable($path)) {
        throw new RuntimeException('Upload directory is not writable.');
    }
}

function learning_image_from_upload(string $tmpPath, string $mime)
{
    if ($mime === 'image/jpeg') {
        return imagecreatefromjpeg($tmpPath);
    }
    if ($mime === 'image/png') {
        return imagecreatefrompng($tmpPath);
    }
    if ($mime === 'image/webp') {
        return imagecreatefromwebp($tmpPath);
    }
    return false;
}

function learning_image_save_jpeg($source, int $sourceWidth, int $sourceHeight, string $destination, int $maxSide = 1400, int $targetMaxBytes = 1200000): void
{
    $scale = 1.0;
    $largestSide = max($sourceWidth, $sourceHeight);
    if ($largestSide > $maxSide) {
        $scale = $maxSide / $largestSide;
    }

    $newWidth = max(1, (int)round($sourceWidth * $scale));
    $newHeight = max(1, (int)round($sourceHeight * $scale));

    $canvas = imagecreatetruecolor($newWidth, $newHeight);
    imageinterlace($canvas, true);

    $white = imagecolorallocate($canvas, 255, 255, 255);
    imagefilledrectangle($canvas, 0, 0, $newWidth, $newHeight, $white);
    imagecopyresampled($canvas, $source, 0, 0, 0, 0, $newWidth, $newHeight, $sourceWidth, $sourceHeight);

    foreach ([84, 78, 72, 66, 60] as $quality) {
        imagejpeg($canvas, $destination, $quality);
        clearstatcache(true, $destination);
        $size = @filesize($destination);
        if ($size !== false && $size <= $targetMaxBytes) {
            imagedestroy($canvas);
            return;
        }
    }

    imagedestroy($canvas);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    learning_image_json(405, ['error' => ['message' => 'Method not allowed.']]);
}

if (!isset($pdo) || !($pdo instanceof PDO)) {
    learning_image_json(500, ['error' => ['message' => 'Database connection unavailable.']]);
}

$centreId = learning_get_centre_id();
$userId = learning_get_user_id();
$userRole = learning_get_user_role();
$courseId = (int)($_GET['course_id'] ?? $_POST['course_id'] ?? 0);

if ($centreId <= 0 || $userId <= 0 || !learning_has_admin_permission($userRole)) {
    learning_image_json(403, ['error' => ['message' => 'You do not have permission to upload course images.']]);
}

$courseStmt = $pdo->prepare("
    SELECT owner_centre_id, visibility, is_platform_course
    FROM rescue_learning_courses
    WHERE course_id = ?
    LIMIT 1
");
$courseStmt->execute([$courseId]);
$course = $courseStmt->fetch(PDO::FETCH_ASSOC);
$canUploadToCourse = $course
    && (
        (int)$course['owner_centre_id'] === $centreId
        || learning_is_platform_admin($userRole)
    );

if ($courseId <= 0 || !$canUploadToCourse) {
    learning_image_json(403, ['error' => ['message' => 'You do not have permission to upload images for this course.']]);
}

if (!extension_loaded('gd')) {
    learning_image_json(500, ['error' => ['message' => 'Image resizing is not available on this server.']]);
}

$file = $_FILES['file'] ?? null;
if (!is_array($file) || (int)($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
    learning_image_json(400, ['error' => ['message' => 'No image was uploaded.']]);
}

if ((int)($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
    learning_image_json(400, ['error' => ['message' => 'Image upload failed.']]);
}

$maxUploadBytes = 8 * 1024 * 1024;
if ((int)($file['size'] ?? 0) > $maxUploadBytes) {
    learning_image_json(413, ['error' => ['message' => 'Image is too large. Maximum upload is 8MB.']]);
}

$tmpPath = (string)($file['tmp_name'] ?? '');
if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
    learning_image_json(400, ['error' => ['message' => 'Invalid upload.']]);
}

$mime = (string)@mime_content_type($tmpPath);
$allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
if (!in_array($mime, $allowedTypes, true)) {
    learning_image_json(415, ['error' => ['message' => 'Unsupported image type. Please upload JPG, PNG, or WebP.']]);
}

$source = learning_image_from_upload($tmpPath, $mime);
if (!$source) {
    learning_image_json(415, ['error' => ['message' => 'Could not read the uploaded image.']]);
}

$sourceWidth = imagesx($source);
$sourceHeight = imagesy($source);
if ($sourceWidth <= 0 || $sourceHeight <= 0) {
    imagedestroy($source);
    $source = null;
    learning_image_json(415, ['error' => ['message' => 'Invalid image dimensions.']]);
}

try {
    $userImagesRoot = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/\\') . '/user_images';
    if ($userImagesRoot === '/user_images') {
        throw new RuntimeException('Document root is unavailable.');
    }

    $relativeDir = 'user_images/courses/' . $courseId;
    $filesystemDir = $userImagesRoot . '/courses/' . $courseId;
    learning_image_ensure_dir($filesystemDir);

    $originalName = (string)($file['name'] ?? 'course-image.jpg');
    $baseName = learning_image_clean_filename((string)pathinfo($originalName, PATHINFO_FILENAME));
    $finalName = $baseName . '-' . date('YmdHis') . '-' . substr(bin2hex(random_bytes(4)), 0, 8) . '.jpg';

    $destinationPath = $filesystemDir . '/' . $finalName;
    learning_image_save_jpeg($source, $sourceWidth, $sourceHeight, $destinationPath);
    imagedestroy($source);
    $source = null;

    if (!is_file($destinationPath)) {
        throw new RuntimeException('Image could not be saved.');
    }

    learning_image_json(200, [
        'location' => $relativeDir . '/' . $finalName,
    ]);
} catch (Throwable $e) {
    if ($source) {
        imagedestroy($source);
    }
    learning_image_json(500, ['error' => ['message' => $e->getMessage()]]);
}
