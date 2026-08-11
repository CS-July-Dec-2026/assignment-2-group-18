<?php
$uploadDir = __DIR__ . '/uploads/';
$files = array_values(array_diff(scandir($uploadDir), ['.', '..', '.gitkeep']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Gallery | PixelNest</title>
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
    <h1>Community Gallery</h1>
    <p class="subtitle"><?= count($files) ?> photo(s) uploaded so far</p>

    <div class="grid">
      <?php foreach ($files as $f): ?>
        <div class="thumb">
          <a href="uploads/<?= rawurlencode($f) ?>" target="_blank">
            <?php
              $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
              if (in_array($ext, ['jpg','jpeg','png','gif'])):
            ?>
              <img src="uploads/<?= rawurlencode($f) ?>" alt="<?= htmlspecialchars($f) ?>">
            <?php else: ?>
              <div class="filecard">📄 <?= htmlspecialchars($f) ?></div>
            <?php endif; ?>
          </a>
          <p class="filename"><?= htmlspecialchars($f) ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  </main>
</body>
</html>
