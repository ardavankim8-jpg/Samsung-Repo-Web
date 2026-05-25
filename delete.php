<?php
/**
 * Samsung Inventory System
 * delete.php — Delete Product (with confirmation page)
 */

$host   = 'localhost';
$dbname = 'samsung';
$user   = 'root';
$pass   = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) {
    die('<div style="padding:2rem;color:red;">DB Error: ' . htmlspecialchars($e->getMessage()) . '</div>');
}

// Validate ID
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: index.php');
    exit;
}

// Fetch product
$stmt = $pdo->prepare("SELECT * FROM samsung WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header('Location: index.php');
    exit;
}

// Handle confirmed deletion (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['confirm'] ?? '') === 'yes') {
    $del = $pdo->prepare("DELETE FROM samsung WHERE id = ?");
    $del->execute([$id]);
    header('Location: index.php?deleted=1');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Delete Product — Samsung Inventory</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    .wrapper { display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: calc(100vh - 64px); }
  </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar">
  <a href="index.php" class="navbar-brand">
    <div class="logo-icon">📱</div>
    <div>
      <span class="brand-name">Samsung</span>
      <span class="brand-sub">Inventory</span>
    </div>
  </a>
</nav>

<div class="wrapper">

  <div class="confirm-card fade-up" style="width:100%;max-width:480px">

    <!-- ICON -->
    <div class="confirm-icon">
      <i class="fas fa-trash-alt" style="color:var(--danger)"></i>
    </div>

    <!-- TITLE -->
    <div class="confirm-title">Delete Product?</div>
    <p style="color:var(--muted);font-size:.9rem">This action is permanent and cannot be undone.</p>

    <!-- PRODUCT DETAIL -->
    <div class="confirm-detail">
      <p><strong>ID:</strong> #<?= htmlspecialchars($product['id']) ?></p>
      <p><strong>Model:</strong> <?= htmlspecialchars($product['Model']) ?></p>
      <p><strong>Storage:</strong> <?= htmlspecialchars($product['Gb']) ?> GB</p>
      <p><strong>Color:</strong> <?= htmlspecialchars($product['Color']) ?></p>
      <p><strong>Price:</strong> ₱<?= number_format($product['price'], 2) ?></p>
      <p><strong>Stocks:</strong> <?= htmlspecialchars($product['Stocks']) ?> units</p>
    </div>

    <!-- ACTIONS -->
    <div class="confirm-footer">
      <a href="index.php" class="btn btn-ghost">
        <i class="fas fa-arrow-left"></i> Cancel
      </a>
      <a href="edit.php?id=<?= $id ?>" class="btn btn-warning">
        <i class="fas fa-pen"></i> Edit Instead
      </a>
      <form method="POST" action="delete.php?id=<?= $id ?>" style="display:inline">
        <input type="hidden" name="confirm" value="yes">
        <button type="submit" class="btn btn-danger">
          <i class="fas fa-trash"></i> Yes, Delete
        </button>
      </form>
    </div>

  </div>

</div>

<script src="script.js"></script>
</body>
</html>