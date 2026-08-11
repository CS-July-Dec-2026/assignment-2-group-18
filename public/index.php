<?php
// PixelNest — a tiny "photo sharing" app used to demonstrate CWE-434 (Insecure File Upload).
$uploadDir = __DIR__ . '/uploads/';
$msg = $_GET['msg'] ?? '';
$err = $_GET['err'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>PixelNest | Photo Sharing</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <header class="nav">
    <div class="logo">📷 PixelNest</div>
    <nav>
      <a href="index.php">Home</a>
      <a href="gallery.php">Gallery</a>
    </nav>
  </header>

  <main class="container">
    <h1>Share a photo with the community</h1>
    <p class="subtitle">Upload a profile picture (JPG, JPEG or PNG). It'll show up in the public gallery instantly.</p>

    <?php if ($msg): ?>
      <div class="alert success">✅ <?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>
    <?php if ($err): ?>
      <div class="alert error">⚠️ <?= htmlspecialchars($err) ?></div>
    <?php endif; ?>

    <form action="upload.php" method="POST" enctype="multipart/form-data" class="upload-card" id="uploadForm">
      <label for="file" class="dropzone">
        <span id="fileLabel">Click to choose an image, or drag it here</span>
        <input type="file" name="file" id="file" accept=".jpg,.jpeg,.png" required>
      </label>
      <button type="submit">Upload Photo</button>
      <p class="hint">Allowed types: .jpg, .jpeg, .png — max 5MB</p>
    </form>
  </main>

  <script>
    // NOTE: this is a client-side convenience check only.
    // It improves UX but provides ZERO security — it can be trivially
    // bypassed with curl/Burp/Postman, so the server must never rely on it.
    const input = document.getElementById('file');
    const label = document.getElementById('fileLabel');
    const form  = document.getElementById('uploadForm');
    const allowed = ['jpg', 'jpeg', 'png'];

    input.addEventListener('change', () => {
      if (input.files.length) label.textContent = input.files[0].name;
    });

    form.addEventListener('submit', (e) => {
      const name = input.files[0]?.name || '';
      const ext = name.split('.').pop().toLowerCase();
      if (!allowed.includes(ext)) {
        e.preventDefault();
        alert('Only JPG/JPEG/PNG files are allowed.');
      }
    });
  </script>
</body>
</html>
