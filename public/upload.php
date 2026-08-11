<?php
/**
 * upload.php — handles photo uploads for PixelNest.
 *
 * =====================================================================
 *  THIS FILE IS INTENTIONALLY VULNERABLE (CWE-434: Unrestricted Upload
 *  of File with Dangerous Type). It is built for a security-class
 *  demonstration. Do not deploy this pattern in a real application.
 * =====================================================================
 */

$uploadDir = __DIR__ . '/uploads/';

function redirect_error($msg) {
    header('Location: index.php?err=' . urlencode($msg));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['file'])) {
    redirect_error('No file received.');
}

$file = $_FILES['file'];

if ($file['error'] !== UPLOAD_ERR_OK) {
    redirect_error('Upload failed (code ' . $file['error'] . ').');
}

$originalName = $file['name'];

// -------------------------------------------------------------------
// [FLAW #1] "Extension check" that only looks for an allowed substring
// anywhere in the filename, instead of validating the TRUE final
// extension. shell.jpg.php contains ".jpg" so this happily passes,
// even though the file the OS/PHP will actually execute is *.php.
// -------------------------------------------------------------------
$looksLikeImage = false;
foreach (['.jpg', '.jpeg', '.png'] as $goodExt) {
    if (stripos($originalName, $goodExt) !== false) {
        $looksLikeImage = true;
        break;
    }
}
if (!$looksLikeImage) {
    redirect_error('Only JPG/JPEG/PNG files are allowed.');
}

// -------------------------------------------------------------------
// [FLAW #2] "Type check" trusts the Content-Type header the *client*
// sent in the multipart request. This is attacker-controlled and can
// be set to "image/jpeg" no matter what the file's real bytes are.
// There is no server-side inspection of the actual file content
// (no getimagesize(), no finfo_file() magic-byte check).
// -------------------------------------------------------------------
$clientMime = $file['type'];
if (!in_array($clientMime, ['image/jpeg', 'image/png', 'image/jpg'])) {
    redirect_error('Invalid file type reported by browser.');
}

// -------------------------------------------------------------------
// [FLAW #3] The file is stored using its original (attacker-supplied)
// filename, directly inside a publicly web-accessible directory that
// has no execution restrictions placed on it (no .htaccess / handler
// override, no "X-Content-Type-Options", nothing). Whatever name the
// attacker chose is exactly the name/extension the file lands under
// and can later be requested directly over HTTP.
// -------------------------------------------------------------------
$destination = $uploadDir . basename($originalName);

if (!move_uploaded_file($file['tmp_name'], $destination)) {
    redirect_error('Could not save the file on the server.');
}

header('Location: index.php?msg=' . urlencode("Uploaded {$originalName} successfully!"));
exit;
