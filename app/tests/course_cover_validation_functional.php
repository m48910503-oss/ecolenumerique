<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "CLI only\n";
    exit(1);
}

$rootDir = dirname(__DIR__, 2);
chdir($rootDir);

require_once $rootDir . '/app/bootstrap.php';

/**
 * @param array<int,string> $errors
 */
function assert_true(bool $condition, string $message, array &$errors): void
{
    if (!$condition) {
        $errors[] = $message;
    }
}

function make_png_file(string $path, int $width, int $height, array $rgb = [51, 102, 153]): void
{
    $signature = "\x89PNG\r\n\x1a\n";
    $ihdr = pack('NNCCCCC', $width, $height, 8, 2, 0, 0, 0);

    $pixel = pack('C3', (int) ($rgb[0] ?? 0), (int) ($rgb[1] ?? 0), (int) ($rgb[2] ?? 0));
    $row = chr(0) . str_repeat($pixel, $width);
    $raw = str_repeat($row, $height);
    $compressed = gzcompress($raw, 9);
    if ($compressed === false) {
        throw new RuntimeException('Unable to compress test PNG data.');
    }

    $chunk = static function (string $type, string $data): string {
        $crc = crc32($type . $data);
        if ($crc < 0) {
            $crc += 4294967296;
        }
        return pack('N', strlen($data)) . $type . $data . pack('N', $crc);
    };

    $png = $signature . $chunk('IHDR', $ihdr) . $chunk('IDAT', $compressed) . $chunk('IEND', '');
    file_put_contents($path, $png);
}

$errors = [];
$tempDir = sys_get_temp_dir();

$validator = new class extends \App\Core\Service {
    public function validate(array $file, array &$errors): ?array
    {
        return $this->validateCourseCoverUpload($file, $errors);
    }
};

$validPath = tempnam($tempDir, 'cover_ok_');
if ($validPath === false) {
    throw new RuntimeException('Unable to create temporary file.');
}
$validPng = $validPath . '.png';
@unlink($validPath);
make_png_file($validPng, 1200, 750);

$validErrors = [];
$validMeta = $validator->validate([
    'tmp_name' => $validPng,
    'size' => filesize($validPng) ?: 0,
    'type' => 'image/png',
    'name' => 'cover.png',
], $validErrors);

assert_true($validErrors === [], 'A 1200 x 750 PNG thumbnail should be accepted.', $errors);
assert_true(is_array($validMeta), 'A valid thumbnail should return normalized metadata.', $errors);
assert_true((string) ($validMeta['extension'] ?? '') === 'png', 'A valid PNG should normalize to the png extension.', $errors);
assert_true((int) ($validMeta['width'] ?? 0) === 1200, 'A valid thumbnail should report the expected width.', $errors);
assert_true((int) ($validMeta['height'] ?? 0) === 750, 'A valid thumbnail should report the expected height.', $errors);

$smallPath = tempnam($tempDir, 'cover_small_');
if ($smallPath === false) {
    throw new RuntimeException('Unable to create temporary file.');
}
$smallPng = $smallPath . '.png';
@unlink($smallPath);
make_png_file($smallPng, 800, 500);

$smallErrors = [];
$smallMeta = $validator->validate([
    'tmp_name' => $smallPng,
    'size' => filesize($smallPng) ?: 0,
    'type' => 'image/png',
    'name' => 'cover-small.png',
], $smallErrors);

assert_true($smallMeta === null, 'A thumbnail smaller than 1200 x 750 should be rejected.', $errors);
assert_true(implode(' | ', $smallErrors) !== '', 'A too-small thumbnail should produce an explicit error.', $errors);
assert_true(str_contains(implode(' | ', $smallErrors), '1200 x 750'), 'A too-small thumbnail should mention the minimum size.', $errors);

$ratioPath = tempnam($tempDir, 'cover_ratio_');
if ($ratioPath === false) {
    throw new RuntimeException('Unable to create temporary file.');
}
$ratioPng = $ratioPath . '.png';
@unlink($ratioPath);
make_png_file($ratioPng, 1366, 768);

$ratioErrors = [];
$ratioMeta = $validator->validate([
    'tmp_name' => $ratioPng,
    'size' => filesize($ratioPng) ?: 0,
    'type' => 'image/png',
    'name' => 'cover-ratio.png',
], $ratioErrors);

assert_true($ratioMeta === null, 'A thumbnail that does not respect 16:10 should be rejected.', $errors);
assert_true(str_contains(implode(' | ', $ratioErrors), '16:10'), 'A wrong-ratio thumbnail should mention the 16:10 format.', $errors);

$textPath = tempnam($tempDir, 'cover_text_');
if ($textPath === false) {
    throw new RuntimeException('Unable to create temporary file.');
}
file_put_contents($textPath, "not an image");

$textErrors = [];
$textMeta = $validator->validate([
    'tmp_name' => $textPath,
    'size' => filesize($textPath) ?: 0,
    'type' => 'text/plain',
    'name' => 'cover.txt',
], $textErrors);

assert_true($textMeta === null, 'A non-image upload should be rejected.', $errors);
assert_true(str_contains(implode(' | ', $textErrors), 'JPG, PNG ou WebP'), 'A non-image upload should mention the allowed formats.', $errors);

@unlink($validPng);
@unlink($smallPng);
@unlink($ratioPng);
@unlink($textPath);

if ($errors !== []) {
    echo "Course cover validation FAILED\n";
    foreach ($errors as $error) {
        echo "- {$error}\n";
    }
    exit(1);
}

echo "Course cover validation OK\n";
exit(0);
