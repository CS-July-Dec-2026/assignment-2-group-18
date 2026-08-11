<?php
/**
 * upload.php — SECURE version.
 * Fixes CWE-434 (Insecure File Upload) present in the original demo.
 */

$uploadDir = __DIR__ . '/uploads/';
$maxBytes  = 5 * 1024 * 1024; // 5MB

// FIX: allow-list keyed by REAL detected image type, not filename/header.
$allowedMimeToExt = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
];

function fail($msg) {
    header('Location: index.php?err=' . urlencode($msg));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['file'])) {
    fail('No file received.');
}

$file = $_FILES['file'];

if ($file['error'] !== UPLOAD_ERR_OK) {
    fail('Upload failed.');
}

if ($file['size'] > $maxBytes) {
    fail('File too large.');
}

// ERROR (old code): checked stripos($name, '.jpg') — true for "shell.jpg.php" too.
// ERROR (old code): trusted $_FILES['type'], which the client can set to anything.
// FIX: read the file's actual bytes with getimagesize(). A PHP script fails
// this check no matter what it's named or what Content-Type was sent.
$imageInfo = @getimagesize($file['tmp_name']);
if ($imageInfo === false || !isset($allowedMimeToExt[$imageInfo['mime']])) {
    fail('Not a valid JPG/PNG image.');
}
$verifiedExt = $allowedMimeToExt[$imageInfo['mime']];

// ERROR (old code): saved file under the attacker-supplied original filename.
// FIX: generate a random name and use ONLY the server-verified extension —
// the attacker's filename/extension never touches the filesystem.
$safeName    = bin2hex(random_bytes(16)) . '.' . $verifiedExt;
$destination = $uploadDir . $safeName;

// FIX (extra hardening): re-encode the image so no extra bytes appended
// after a valid image header (e.g. hidden PHP code) survive the save.
$img = ($verifiedExt === 'png') ? imagecreatefrompng($file['tmp_name'])
                                 : imagecreatefromjpeg($file['tmp_name']);
if ($img === false) {
    fail('Could not process image.');
}
($verifiedExt === 'png') ? imagepng($img, $destination) : imagejpeg($img, $destination, 90);
imagedestroy($img);

// ERROR (old code): uploads/ had no restriction, so a saved .php file would
// execute. FIX: uploads/.htaccess (included in this folder) disables the
// PHP engine and strips script handlers for that directory — see .htaccess.

header('Location: index.php?msg=' . urlencode('Uploaded successfully!'));
exit;
