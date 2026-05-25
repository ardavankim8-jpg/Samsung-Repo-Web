<?php
// Samsung Inventory System - edit.php
$host = "localhost";
$user = "root";
$password = "";
$database = "samsung";

$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Ensure photo column exists
$conn->query("ALTER TABLE samsung ADD COLUMN IF NOT EXISTS photo VARCHAR(255) DEFAULT NULL");

if (!isset($_GET["id"])) { header("Location: index.php"); exit; }
$id   = (int)$_GET["id"];
$item = $conn->query("SELECT * FROM samsung WHERE id=$id")->fetch_assoc();
if (!$item) { header("Location: index.php"); exit; }

$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $model  = $conn->real_escape_string($_POST["model"]);
    $gb     = (int)$_POST["gb"];
    $color  = $conn->real_escape_string($_POST["color"]);
    $price  = (float)$_POST["price"];
    $stocks = (int)$_POST["stocks"];
    $photo  = $item["photo"] ?? ""; // keep existing by default

    // Handle new photo upload
    if (!empty($_FILES["photo"]["name"])) {
        $ext     = strtolower(pathinfo($_FILES["photo"]["name"], PATHINFO_EXTENSION));
        $allowed = ["jpg", "jpeg", "png", "webp"];
        if (in_array($ext, $allowed)) {
            $filename = "phone_" . time() . "_" . rand(100, 999) . "." . $ext;
            $dest     = "uploads/" . $filename;
            if (!is_dir("uploads")) mkdir("uploads", 0755, true);
            if (move_uploaded_file($_FILES["photo"]["tmp_name"], $dest)) {
                // Delete old photo if exists
                if ($photo && file_exists("uploads/" . $photo)) unlink("uploads/" . $photo);
                $photo = $filename;
            }
        } else {
            $message = "Invalid image format. Use JPG, PNG, or WebP.";
            $message_type = "error";
        }
    }

    // Handle photo removal
    if (isset($_POST["remove_photo"]) && $_POST["remove_photo"] === "1") {
        if ($photo && file_exists("uploads/" . $photo)) unlink("uploads/" . $photo);
        $photo = "";
    }

    if (!$message) {
        $photoEsc = $conn->real_escape_string($photo);
        $sql = "UPDATE samsung SET Model='$model', Gb=$gb, Color='$color', price=$price, Stocks=$stocks, photo='$photoEsc' WHERE id=$id";
        if ($conn->query($sql)) {
            header("Location: index.php?msg=updated");
            exit;
        } else {
            $message = "Error updating product: " . $conn->error;
            $message_type = "error";
            $item = array_merge($item, $_POST);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Edit Product — Samsung Inventory</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet"/>
<style>
  :root {
    --bg:#0a0f1e; --surface:#111827; --surface2:#1a2235;
    --border:rgba(0,178,255,0.12); --blue:#00b2ff; --accent:#1a6cff;
    --text:#e8f0fe; --muted:#6b7fa3; --success:#00d68f; --danger:#ff4757;
    --warning:#ffb300; --radius:14px; --glow:0 0 24px rgba(0,178,255,0.18);
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
  header { position: sticky; top: 0; z-index: 100; display: flex; align-items: center; padding: 0 2.5rem; height: 68px; background: rgba(10,15,30,0.85); backdrop-filter: blur(18px); border-bottom: 1px solid var(--border); }
  .logo { display: flex; align-items: center; gap: 12px; }
  .logo-mark { width: 38px; height: 38px; background: linear-gradient(135deg, var(--blue), var(--accent)); border-radius: 10px; display: flex; align-items: center; justify-content: center; font-family: 'Syne', sans-serif; font-weight: 800; font-size: 18px; color: #fff; box-shadow: var(--glow); }
  .logo-text { font-family: 'Syne', sans-serif; font-weight: 700; font-size: 1.15rem; }
  .logo-text span { color: var(--blue); }
  .wrapper { position: relative; z-index: 1; max-width: 560px; margin: 3rem auto; padding: 0 1.5rem; width: 100%; }
  .page-title { margin-bottom: 1.75rem; }
  .page-title h1 { font-family: 'Syne', sans-serif; font-size: 1.8rem; font-weight: 800; }
  .page-title h1 span { color: var(--warning); }
  .page-title p { color: var(--muted); font-size: .9rem; margin-top: 4px; }
  .id-badge { display: inline-block; background: rgba(0,178,255,.1); border: 1px solid rgba(0,178,255,.25); color: var(--blue); font-size: .75rem; font-weight: 500; padding: 3px 10px; border-radius: 20px; margin-top: 8px; }
  .alert { padding: .85rem 1.2rem; border-radius: 10px; margin-bottom: 1.5rem; font-size: .88rem; display: flex; align-items: center; gap: 10px; }
  .alert.error { background: rgba(255,71,87,.1); border: 1px solid rgba(255,71,87,.3); color: var(--danger); }
  .card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden; }
  .card-header { padding: 1.1rem 1.5rem; border-bottom: 1px solid var(--border); display: flex; align-items: center; gap: 10px; font-family: 'Syne', sans-serif; font-weight: 700; font-size: .95rem; }
  .dot { width: 8px; height: 8px; border-radius: 50%; background: var(--warning); box-shadow: 0 0 8px var(--warning); }
  .card-body { padding: 1.5rem; }
  .form-group { margin-bottom: 1.1rem; }
  .form-group label { display: block; font-size: .78rem; color: var(--muted); text-transform: uppercase; letter-spacing: .07em; margin-bottom: 6px; }
  .form-group input, .form-group select { width: 100%; background: var(--surface2); border: 1px solid var(--border); border-radius: 9px; padding: .65rem .9rem; color: var(--text); font-family: 'DM Sans', sans-serif; font-size: .9rem; transition: border-color .2s, box-shadow .2s; outline: none; }
  .form-group input:focus, .form-group select:focus { border-color: var(--blue); box-shadow: 0 0 0 3px rgba(0,178,255,.12); }
  .form-group select option { background: var(--surface2); }

  /* Current photo box */
  .current-photo-box {
    background: var(--surface2); border: 1px solid var(--border);
    border-radius: 10px; padding: 12px;
    display: flex; align-items: center; gap: 12px; margin-bottom: 10px;
  }
  .current-photo-box img { width: 64px; height: 78px; object-fit: contain; border-radius: 6px; background: var(--surface); border: 1px solid var(--border); }
  .current-photo-info { flex: 1; }
  .current-photo-info p { font-size: .82rem; color: var(--muted); margin-bottom: 6px; }
  .current-photo-info strong { color: var(--text); font-size: .88rem; }
  .btn-remove-photo { background: rgba(255,71,87,.1); color: var(--danger); border: 1px solid rgba(255,71,87,.25); border-radius: 7px; padding: 4px 10px; font-size: .75rem; cursor: pointer; display: inline-flex; align-items: center; gap: 5px; font-family: 'DM Sans', sans-serif; }
  .btn-remove-photo:hover { background: rgba(255,71,87,.2); }
  .no-photo-box { background: var(--surface2); border: 1px dashed var(--border); border-radius: 10px; padding: 10px 12px; font-size: .82rem; color: var(--muted); margin-bottom: 10px; display: flex; align-items: center; gap: 8px; }

  /* Upload area */
  .upload-area { border: 2px dashed var(--border); border-radius: 10px; padding: 1.2rem; text-align: center; cursor: pointer; transition: border-color .2s, background .2s; position: relative; }
  .upload-area:hover { border-color: var(--blue); background: rgba(0,178,255,.04); }
  .upload-area input[type="file"] { position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%; }
  .upload-icon { font-size: 1.8rem; margin-bottom: 6px; display: block; }
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
    <h1>Edit <span>Product</span></h1>
    <p>Update the details for this item</p>
    <span class="id-badge">ID #<?= $id ?> · <?= htmlspecialchars($item['Model']) ?></span>
  </div>

  <?php if ($message): ?>
  <div class="alert <?= $message_type ?>">✖ <?= htmlspecialchars($message) ?></div>
  <?php endif; ?>

  <div class="card">
    <div class="card-header"><div class="dot"></div> Edit Product Details</div>
    <div class="card-body">
      <form method="POST" action="edit.php?id=<?= $id ?>" enctype="multipart/form-data">
        <input type="hidden" name="remove_photo" id="removePhotoInput" value="0">

        <div class="form-group">
          <label>Model Name</label>
          <input type="text" name="model" value="<?= htmlspecialchars($item['Model']) ?>" required>
        </div>
        <div class="form-group">
          <label>Storage (GB)</label>
          <select name="gb">
            <?php foreach ([64,128,256,512,1024] as $g): ?>
              <option value="<?= $g ?>" <?= $item['Gb'] == $g ? 'selected' : '' ?>><?= $g ?> GB</option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Color</label>
          <input type="text" name="color" value="<?= htmlspecialchars($item['Color']) ?>" required>
        </div>
        <div class="form-group">
          <label>Price (₱)</label>
          <input type="number" name="price" step="0.01" value="<?= $item['price'] ?>" required>
        </div>
        <div class="form-group">
          <label>Stocks</label>
          <input type="number" name="stocks" value="<?= $item['Stocks'] ?>" required>
        </div>

        <!-- PHOTO SECTION -->
        <div class="form-group">
          <label>Phone Photo</label>

          <?php if (!empty($item['photo']) && file_exists("uploads/" . $item['photo'])): ?>
          <!-- Show current photo -->
          <div class="current-photo-box" id="currentPhotoBox">
            <img src="uploads/<?= htmlspecialchars($item['photo']) ?>" alt="Current phone photo">
            <div class="current-photo-info">
              <p>Current photo</p>
              <strong><?= htmlspecialchars($item['photo']) ?></strong><br><br>
              <button type="button" class="btn-remove-photo" onclick="removePhoto()">✕ Remove photo</button>
            </div>
          </div>
          <div class="upload-hint" style="font-size:.78rem;color:var(--muted);margin-bottom:8px" id="replaceHint">
            — or upload a new one to replace it —
          </div>
          <?php else: ?>
          <div class="no-photo-box">📷 No photo uploaded yet</div>
          <?php endif; ?>

          <div class="upload-area" id="uploadArea">
            <input type="file" name="photo" id="photoInput" accept="image/jpeg,image/png,image/webp" onchange="previewPhoto(this)">
            <span class="upload-icon">📷</span>
            <div class="upload-hint" id="uploadHint"><span>Click to upload</span> or drag & drop<br>JPG, PNG, WebP · Max 5MB</div>
          </div>
          <div class="preview-wrap" id="previewWrap">
            <img src="" id="photoPreview" alt="New photo preview">
          </div>
        </div>

        <div class="btn-row">
          <a href="index.php" class="btn btn-cancel">✕ Cancel</a>
          <button type="submit" class="btn btn-primary">💾 Update Product</button>
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
  const hint = document.getElementById('uploadHint');
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = e => { img.src = e.target.result; wrap.style.display = 'block'; };
    reader.readAsDataURL(input.files[0]);
    hint.innerHTML = '<span style="color:var(--success)">✔ ' + input.files[0].name + '</span>';
  }
}

function removePhoto() {
  if (!confirm('Remove the current photo?')) return;
  document.getElementById('removePhotoInput').value = '1';
  const box = document.getElementById('currentPhotoBox');
  if (box) box.style.display = 'none';
  const hint = document.getElementById('replaceHint');
  if (hint) hint.style.display = 'none';
}
</script>
</body>
</html>
<?php $conn->close(); ?>