<?php
// Samsung Inventory System - create.php
$host = "localhost";
$user = "root";
$password = "";
$database = "samsung";

$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Ensure photo column exists
$conn->query("ALTER TABLE samsung ADD COLUMN IF NOT EXISTS photo VARCHAR(255) DEFAULT NULL");

$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $model  = $conn->real_escape_string($_POST["model"]);
    $gb     = (int)$_POST["gb"];
    $color  = $conn->real_escape_string($_POST["color"]);
    $price  = (float)$_POST["price"];
    $stocks = (int)$_POST["stocks"];
    $photo  = "";

    // Handle photo upload
    if (!empty($_FILES["photo"]["name"])) {
        $ext     = strtolower(pathinfo($_FILES["photo"]["name"], PATHINFO_EXTENSION));
        $allowed = ["jpg", "jpeg", "png", "webp"];
        if (in_array($ext, $allowed)) {
            $filename = "phone_" . time() . "_" . rand(100, 999) . "." . $ext;
            $dest     = "uploads/" . $filename;
            if (!is_dir("uploads")) mkdir("uploads", 0755, true);
            if (move_uploaded_file($_FILES["photo"]["tmp_name"], $dest)) {
                $photo = $filename;
            } else {
                $message = "Failed to save image. Check folder permissions.";
                $message_type = "error";
            }
        } else {
            $message = "Invalid image format. Use JPG, PNG, or WebP.";
            $message_type = "error";
        }
    }

    if (!$message) {
        $photoEsc = $conn->real_escape_string($photo);
        $sql = "INSERT INTO samsung (Model, Gb, Color, price, Stocks, photo) VALUES ('$model', $gb, '$color', $price, $stocks, '$photoEsc')";
        if ($conn->query($sql)) {
            header("Location: index.php?msg=added");
            exit;
        } else {
            $message = "Error adding product: " . $conn->error;
            $message_type = "error";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Add Product — Samsung Inventory</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet"/>
<style>
  :root {
    --bg:#0a0f1e; --surface:#111827; --surface2:#1a2235;
    --border:rgba(0,178,255,0.12); --blue:#00b2ff; --accent:#1a6cff;
    --text:#e8f0fe; --muted:#6b7fa3; --success:#00d68f; --danger:#ff4757;
    --radius:14px; --glow:0 0 24px rgba(0,178,255,0.18);
  }
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: 'DM Sans', sans-serif; background: var(--bg); color: var(--text); min-height: 100vh; display: flex; flex-direction: column; }
  body::before {
    content: ''; position: fixed; inset: 0;
    background:
      radial-gradient(ellipse 80% 50% at 10% 10%, rgba(0,102,204,0.18) 0%, transparent 60%),
      radial-gradient(ellipse 60% 40% at 90% 80%, rgba(0,178,255,0.10) 0%, transparent 60%);
    pointer-events: none; z-index: 0;
  }
  header { position: sticky; top: 0; z-index: 100; display: flex; align-items: center; justify-content: space-between; padding: 0 2.5rem; height: 68px; background: rgba(10,15,30,0.85); backdrop-filter: blur(18px); border-bottom: 1px solid var(--border); }
  .logo { display: flex; align-items: center; gap: 12px; }
  .logo-mark { width: 38px; height: 38px; background: linear-gradient(135deg, var(--blue), var(--accent)); border-radius: 10px; display: flex; align-items: center; justify-content: center; font-family: 'Syne', sans-serif; font-weight: 800; font-size: 18px; color: #fff; box-shadow: var(--glow); }
  .logo-text { font-family: 'Syne', sans-serif; font-weight: 700; font-size: 1.15rem; }
  .logo-text span { color: var(--blue); }
  .wrapper { position: relative; z-index: 1; max-width: 560px; margin: 3rem auto; padding: 0 1.5rem; width: 100%; }
  .page-title { margin-bottom: 1.75rem; }
  .page-title h1 { font-family: 'Syne', sans-serif; font-size: 1.8rem; font-weight: 800; }
  .page-title h1 span { color: var(--blue); }
  .page-title p { color: var(--muted); font-size: .9rem; margin-top: 4px; }
  .alert { padding: .85rem 1.2rem; border-radius: 10px; margin-bottom: 1.5rem; font-size: .88rem; display: flex; align-items: center; gap: 10px; }
  .alert.error { background: rgba(255,71,87,.1); border: 1px solid rgba(255,71,87,.3); color: var(--danger); }
  .card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden; }
  .card-header { padding: 1.1rem 1.5rem; border-bottom: 1px solid var(--border); display: flex; align-items: center; gap: 10px; font-family: 'Syne', sans-serif; font-weight: 700; font-size: .95rem; }
  .dot { width: 8px; height: 8px; border-radius: 50%; background: var(--blue); box-shadow: 0 0 8px var(--blue); }
  .card-body { padding: 1.5rem; }
  .form-group { margin-bottom: 1.1rem; }
  .form-group label { display: block; font-size: .78rem; color: var(--muted); text-transform: uppercase; letter-spacing: .07em; margin-bottom: 6px; }
  .form-group input, .form-group select { width: 100%; background: var(--surface2); border: 1px solid var(--border); border-radius: 9px; padding: .65rem .9rem; color: var(--text); font-family: 'DM Sans', sans-serif; font-size: .9rem; transition: border-color .2s, box-shadow .2s; outline: none; }
  .form-group input:focus, .form-group select:focus { border-color: var(--blue); box-shadow: 0 0 0 3px rgba(0,178,255,.12); }
  .form-group select option { background: var(--surface2); }

  /* Photo upload */
  .upload-area { border: 2px dashed var(--border); border-radius: 10px; padding: 1.2rem; text-align: center; cursor: pointer; transition: border-color .2s, background .2s; position: relative; }
  .upload-area:hover { border-color: var(--blue); background: rgba(0,178,255,.04); }
  .upload-area input[type="file"] { position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%; }
  .upload-icon { font-size: 2rem; margin-bottom: 6px; display: block; }
  .upload-hint { font-size: .8rem; color: var(--muted); }
  .upload-hint span { color: var(--blue); font-weight: 500; }
  .preview-wrap { display: none; margin-top: 10px; }
  .preview-wrap img { width: 100%; max-height: 180px; object-fit: contain; border-radius: 10px; background: var(--surface2); border: 1px solid var(--border); }

  .btn-row { display: flex; gap: 10px; margin-top: 1.5rem; }
  .btn { display: inline-flex; align-items: center; justify-content: center; gap: 7px; padding: .65rem 1.3rem; border-radius: 9px; border: none; cursor: pointer; font-family: 'DM Sans', sans-serif; font-size: .9rem; font-weight: 500; transition: all .18s; text-decoration: none; }
  .btn-primary { background: linear-gradient(135deg, var(--blue), var(--accent)); color: #fff; flex: 1; box-shadow: 0 4px 16px rgba(0,178,255,.25); }
  .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 6px 24px rgba(0,178,255,.35); }
  .btn-cancel { background: var(--surface2); color: var(--muted); border: 1px solid var(--border); padding: .65rem 1.2rem; }
  .btn-cancel:hover { color: var(--text); }
  footer { text-align: center; padding: 1.5rem; color: var(--muted); font-size: .78rem; border-top: 1px solid var(--border); margin-top: auto; }
</style>
</head>
<body>
<header>
  <div class="logo">
    <div class="logo-mark">S</div>
    <div class="logo-text"><span>Samsung</span> Inventory</div>
  </div>
</header>

<div class="wrapper">
  <div class="page-title">
    <h1>Add <span>New Product</span></h1>
    <p>Fill in the details to add a new Samsung device</p>
  </div>

  <?php if ($message): ?>
  <div class="alert <?= $message_type ?>">✖ <?= htmlspecialchars($message) ?></div>
  <?php endif; ?>

  <div class="card">
    <div class="card-header"><div class="dot"></div> Product Details</div>
    <div class="card-body">
      <form method="POST" action="create.php" enctype="multipart/form-data">

        <div class="form-group">
          <label>Model Name</label>
          <input type="text" name="model" placeholder="e.g. Galaxy S24 Ultra" value="<?= htmlspecialchars($_POST['model'] ?? '') ?>" required>
        </div>
        <div class="form-group">
          <label>Storage (GB)</label>
          <select name="gb">
            <?php foreach ([64,128,256,512,1024] as $g): ?>
              <option value="<?= $g ?>" <?= ($_POST['gb'] ?? '') == $g ? 'selected' : '' ?>><?= $g ?> GB</option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Color</label>
          <input type="text" name="color" placeholder="e.g. Phantom Black" value="<?= htmlspecialchars($_POST['color'] ?? '') ?>" required>
        </div>
        <div class="form-group">
          <label>Price (₱)</label>
          <input type="number" name="price" step="0.01" placeholder="0.00" value="<?= $_POST['price'] ?? '' ?>" required>
        </div>
        <div class="form-group">
          <label>Stocks</label>
          <input type="number" name="stocks" placeholder="0" value="<?= $_POST['stocks'] ?? '' ?>" required>
        </div>

        <!-- PHOTO -->
        <div class="form-group">
          <label>Phone Photo <span style="color:var(--muted);font-size:.72rem;text-transform:none">(optional)</span></label>
          <div class="upload-area" id="uploadArea">
            <input type="file" name="photo" id="photoInput" accept="image/jpeg,image/png,image/webp" onchange="previewPhoto(this)">
            <span class="upload-icon">📷</span>
            <div class="upload-hint"><span>Click to upload</span> or drag & drop<br>JPG, PNG, WebP · Max 5MB</div>
          </div>
          <div class="preview-wrap" id="previewWrap">
            <img src="" id="photoPreview" alt="Preview">
          </div>
        </div>

        <div class="btn-row">
          <a href="index.php" class="btn btn-cancel">✕ Cancel</a>
          <button type="submit" class="btn btn-primary">＋ Add Product</button>
        </div>
      </form>
    </div>
  </div>
</div>

<footer>Samsung Inventory System · <?= date('Y') ?></footer>

<script>
function previewPhoto(input) {
  const wrap = document.getElementById('previewWrap');
  const img  = document.getElementById('photoPreview');
  const hint = document.querySelector('.upload-hint');
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = e => { img.src = e.target.result; wrap.style.display = 'block'; };
    reader.readAsDataURL(input.files[0]);
    hint.innerHTML = '<span style="color:var(--success)">✔ ' + input.files[0].name + '</span>';
  }
}
</script>
</body>
</html>
<?php $conn->close(); ?>