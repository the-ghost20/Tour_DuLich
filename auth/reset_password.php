<?php
declare(strict_types=1);
$activePage = '';
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Dat lai mat khau</title>
  <link rel="stylesheet" href="../assets/css/style.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
</head>
<body>
<?php
$u = '../frontend/';
$a = '';
require __DIR__ . '/../includes/header.php';
?>
<div class="container" style="padding: 3rem 1rem; max-width: 900px;">
  <h1 style="margin-bottom: 1rem;">Dat lai mat khau</h1>
  <p style="color: #64748b;">Noi dung se duoc bo sung trong phien ban tiep theo.</p>
</div>
<?php
$u = '../frontend/';
$a = '';
require __DIR__ . '/../includes/footer.php';
?>
</body>
</html>
