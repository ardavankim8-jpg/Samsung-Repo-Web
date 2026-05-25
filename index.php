<?php
// Samsung Inventory System - index.php
$host = "localhost";
$user = "root";
$password = "";
$database = "samsung";

$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$conn->query("ALTER TABLE samsung ADD COLUMN IF NOT EXISTS photo VARCHAR(255) DEFAULT NULL");

$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST["action"])) {

        if ($_POST["action"] === "add") {
            $model  = $conn->real_escape_string($_POST["model"]);
            $gb     = (int)$_POST["gb"];
            $color  = $conn->real_escape_string($_POST["color"]);
            $price  = (float)$_POST["price"];
            $stocks = (int)$_POST["stocks"];
            $photo  = "";

            if (!empty($_FILES["photo"]["name"])) {
                $ext     = strtolower(pathinfo($_FILES["photo"]["name"], PATHINFO_EXTENSION));
                $allowed = ["jpg","jpeg","png","webp"];
                if (in_array($ext, $allowed)) {
                    $filename = "phone_" . time() . "_" . rand(100,999) . "." . $ext;
                    $dest     = "uploads/" . $filename;
                    if (!is_dir("uploads")) mkdir("uploads", 0755, true);
                    if (move_uploaded_file($_FILES["photo"]["tmp_name"], $dest)) $photo = $filename;
                } else {
                    $message = "Invalid image format. Use JPG, PNG, or WebP.";
                    $message_type = "error";
                }
            }

           if (!$message) {
                // ── Duplicate check ──────────────────────────────────
                $dupCheck = $conn->query("SELECT id FROM samsung WHERE LOWER(Model) = LOWER('$model')");
                if ($dupCheck && $dupCheck->num_rows > 0) {
                    $message      = "\"" . htmlspecialchars($model) . "\" is already in the list. Please use a different model name or edit the existing entry.";
                    $message_type = "error";
                    // Clean up uploaded photo since we won't save the record
                    if ($photo && file_exists("uploads/" . $photo)) unlink("uploads/" . $photo);
                } else {
                    $photoEsc = $conn->real_escape_string($photo);
                    $sql = "INSERT INTO samsung (Model, Gb, Color, price, Stocks, photo) VALUES ('$model', $gb, '$color', $price, $stocks, '$photoEsc')";
                    if ($conn->query($sql)) { $message = "Product added successfully!"; $message_type = "success"; }
                    else { $message = "Error: " . $conn->error; $message_type = "error"; }
                }
            }

        } elseif ($_POST["action"] === "delete" && isset($_POST["id"])) {
            $id = (int)$_POST["id"];
            $row = $conn->query("SELECT photo FROM samsung WHERE id=$id")->fetch_assoc();
            if ($row && $row["photo"] && file_exists("uploads/" . $row["photo"])) unlink("uploads/" . $row["photo"]);
            if ($conn->query("DELETE FROM samsung WHERE id=$id")) { $message = "Product deleted."; $message_type = "success"; }

        } elseif ($_POST["action"] === "edit") {
            $id     = (int)$_POST["id"];
            $model  = $conn->real_escape_string($_POST["model"]);
            $gb     = (int)$_POST["gb"];
            $color  = $conn->real_escape_string($_POST["color"]);
            $price  = (float)$_POST["price"];
            $stocks = (int)$_POST["stocks"];
            $existing = $conn->query("SELECT photo FROM samsung WHERE id=$id")->fetch_assoc();
            $photo = $existing["photo"] ?? "";

            if (!empty($_FILES["photo"]["name"])) {
                $ext     = strtolower(pathinfo($_FILES["photo"]["name"], PATHINFO_EXTENSION));
                $allowed = ["jpg","jpeg","png","webp"];
                if (in_array($ext, $allowed)) {
                    $filename = "phone_" . time() . "_" . rand(100,999) . "." . $ext;
                    $dest     = "uploads/" . $filename;
                    if (!is_dir("uploads")) mkdir("uploads", 0755, true);
                    if (move_uploaded_file($_FILES["photo"]["tmp_name"], $dest)) {
                        if ($photo && file_exists("uploads/" . $photo)) unlink("uploads/" . $photo);
                        $photo = $filename;
                    }
                }
            }

            $photoEsc = $conn->real_escape_string($photo);
            if ($conn->query("UPDATE samsung SET Model='$model', Gb=$gb, Color='$color', price=$price, Stocks=$stocks, photo='$photoEsc' WHERE id=$id")) {
                $message = "Product updated successfully!"; $message_type = "success";
            }
        }
    }
}

$search = isset($_GET["search"]) ? $conn->real_escape_string($_GET["search"]) : "";
$where  = $search ? "WHERE Model LIKE '%$search%' OR Color LIKE '%$search%'" : "";
$result = $conn->query("SELECT * FROM samsung $where ORDER BY id DESC");

$total_products = $conn->query("SELECT COUNT(*) as c FROM samsung")->fetch_assoc()["c"];
$total_stock    = $conn->query("SELECT SUM(Stocks) as s FROM samsung")->fetch_assoc()["s"] ?? 0;
$total_value    = $conn->query("SELECT SUM(price * Stocks) as v FROM samsung")->fetch_assoc()["v"] ?? 0;
$low_stock      = $conn->query("SELECT COUNT(*) as c FROM samsung WHERE Stocks <= 5")->fetch_assoc()["c"];

$edit_item = null;
if (isset($_GET["edit_id"])) {
    $eid = (int)$_GET["edit_id"];
    $edit_item = $conn->query("SELECT * FROM samsung WHERE id=$eid")->fetch_assoc();
}

// Collect all rows for the detail modal (JSON)
$all_rows_res = $conn->query("SELECT * FROM samsung ORDER BY id DESC");
$all_rows = [];
while ($r = $all_rows_res->fetch_assoc()) $all_rows[] = $r;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Samsung Inventory System</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet"/>
<style>
  :root {
    --bg:#0a0f1e; --surface:#111827; --surface2:#1a2235;
    --border:rgba(0,178,255,0.12); --blue:#00b2ff; --accent:#1a6cff;
    --text:#e8f0fe; --muted:#6b7fa3; --success:#00d68f;
    --danger:#ff4757; --warning:#ffb300;
    --radius:14px; --glow:0 0 24px rgba(0,178,255,0.18);
  }
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: 'DM Sans', sans-serif; background: var(--bg); color: var(--text); min-height: 100vh; overflow-x: hidden; }
  body::before {
    content: ''; position: fixed; inset: 0;
    background:
      radial-gradient(ellipse 80% 50% at 10% 10%, rgba(0,102,204,0.18) 0%, transparent 60%),
      radial-gradient(ellipse 60% 40% at 90% 80%, rgba(0,178,255,0.10) 0%, transparent 60%);
    pointer-events: none; z-index: 0;
  }

  /* HEADER */
  header { position: sticky; top: 0; z-index: 100; display: flex; align-items: center; justify-content: space-between; padding: 0 2.5rem; height: 68px; background: rgba(10,15,30,0.85); backdrop-filter: blur(18px); border-bottom: 1px solid var(--border); }
  .logo { display: flex; align-items: center; gap: 12px; }
  .logo-mark { width: 38px; height: 38px; background: linear-gradient(135deg, var(--blue), var(--accent)); border-radius: 10px; display: flex; align-items: center; justify-content: center; font-family: 'Syne', sans-serif; font-weight: 800; font-size: 18px; color: #fff; box-shadow: var(--glow); }
  .logo-text { font-family: 'Syne', sans-serif; font-weight: 700; font-size: 1.15rem; }
  .logo-text span { color: var(--blue); }
  .header-right { display: flex; align-items: center; gap: 14px; }
  .header-badge { background: rgba(0,178,255,0.1); border: 1px solid rgba(0,178,255,0.25); color: var(--blue); font-size: .75rem; font-weight: 500; padding: 4px 12px; border-radius: 20px; }
  .header-time { color: var(--muted); font-size: .8rem; }

  /* LAYOUT */
  .wrapper { position: relative; z-index: 1; max-width: 1400px; margin: 0 auto; padding: 2rem 2rem 4rem; }
  .page-title { margin-bottom: 2rem; }
  .page-title h1 { font-family: 'Syne', sans-serif; font-size: 2rem; font-weight: 800; letter-spacing: -0.03em; }
  .page-title h1 span { color: var(--blue); }
  .page-title p { color: var(--muted); margin-top: 4px; font-size: .9rem; }

  /* STATS */
  .stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 2rem; }
  .stat-card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); padding: 1.25rem 1.5rem; position: relative; overflow: hidden; transition: transform .2s, box-shadow .2s; }
  .stat-card:hover { transform: translateY(-3px); box-shadow: var(--glow); }
  .stat-card::after { content: ''; position: absolute; bottom: 0; left: 0; right: 0; height: 3px; background: linear-gradient(90deg, var(--blue), var(--accent)); }
  .stat-label { font-size: .75rem; color: var(--muted); text-transform: uppercase; letter-spacing: .08em; margin-bottom: 8px; }
  .stat-value { font-family: 'Syne', sans-serif; font-size: 1.9rem; font-weight: 700; }
  .stat-value.blue { color: var(--blue); } .stat-value.success { color: var(--success); } .stat-value.warning { color: var(--warning); }
  .stat-icon { position: absolute; top: 1rem; right: 1rem; font-size: 1.6rem; opacity: .18; }

  /* ALERT */
  .alert { padding: .85rem 1.2rem; border-radius: 10px; margin-bottom: 1.5rem; font-size: .88rem; display: flex; align-items: center; gap: 10px; animation: slideIn .3s ease; }
  .alert.success { background: rgba(0,214,143,.1); border: 1px solid rgba(0,214,143,.3); color: var(--success); }
  .alert.error   { background: rgba(255,71,87,.1);  border: 1px solid rgba(255,71,87,.3);  color: var(--danger); }

  /* GRID */
  .main-grid { display: grid; grid-template-columns: 340px 1fr; gap: 1.5rem; }
  .card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden; }
  .card-header { padding: 1.1rem 1.5rem; border-bottom: 1px solid var(--border); display: flex; align-items: center; gap: 10px; font-family: 'Syne', sans-serif; font-weight: 700; font-size: .95rem; }
  .card-header .dot { width: 8px; height: 8px; border-radius: 50%; background: var(--blue); box-shadow: 0 0 8px var(--blue); }
  .card-body { padding: 1.5rem; }

  /* FORM */
  .form-group { margin-bottom: 1rem; }
  .form-group label { display: block; font-size: .78rem; color: var(--muted); text-transform: uppercase; letter-spacing: .07em; margin-bottom: 6px; }
  .form-group input, .form-group select { width: 100%; background: var(--surface2); border: 1px solid var(--border); border-radius: 9px; padding: .6rem .9rem; color: var(--text); font-family: 'DM Sans', sans-serif; font-size: .9rem; transition: border-color .2s, box-shadow .2s; outline: none; }
  .form-group input:focus, .form-group select:focus { border-color: var(--blue); box-shadow: 0 0 0 3px rgba(0,178,255,.12); }
  .form-group select option { background: var(--surface2); }

  /* UPLOAD */
  .upload-area { border: 2px dashed var(--border); border-radius: 10px; padding: 1rem; text-align: center; cursor: pointer; transition: border-color .2s, background .2s; position: relative; }
  .upload-area:hover { border-color: var(--blue); background: rgba(0,178,255,.04); }
  .upload-area input[type="file"] { position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%; }
  .upload-icon { font-size: 1.6rem; margin-bottom: 6px; opacity: .5; }
  .upload-label { font-size: .78rem; color: var(--muted); }
  .upload-label span { color: var(--blue); }
  .photo-preview-wrap { margin-top: 8px; display: none; }
  .photo-preview-wrap img { width: 100%; max-height: 130px; object-fit: contain; border-radius: 8px; background: var(--surface2); border: 1px solid var(--border); }
  .existing-photo { margin-bottom: 8px; }
  .existing-photo img { width: 100%; max-height: 120px; object-fit: contain; border-radius: 8px; background: var(--surface2); border: 1px solid var(--border); }
  .existing-label { font-size: .72rem; color: var(--muted); margin-bottom: 4px; }

  /* BUTTONS */
  .btn { display: inline-flex; align-items: center; justify-content: center; gap: 7px; padding: .65rem 1.3rem; border-radius: 9px; border: none; cursor: pointer; font-family: 'DM Sans', sans-serif; font-size: .88rem; font-weight: 500; transition: all .18s; text-decoration: none; }
  .btn-primary { background: linear-gradient(135deg, var(--blue), var(--accent)); color: #fff; width: 100%; box-shadow: 0 4px 16px rgba(0,178,255,.25); }
  .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 6px 24px rgba(0,178,255,.35); }
  .btn-sm { padding: .38rem .75rem; font-size: .78rem; }
  .btn-edit { background: rgba(0,178,255,.12); color: var(--blue); border: 1px solid rgba(0,178,255,.25); }
  .btn-edit:hover { background: rgba(0,178,255,.22); }
  .btn-del  { background: rgba(255,71,87,.1); color: var(--danger); border: 1px solid rgba(255,71,87,.25); }
  .btn-del:hover { background: rgba(255,71,87,.2); }
  .btn-cancel { background: var(--surface2); color: var(--muted); width: 100%; margin-top: 8px; }

  /* TABLE */
  .table-wrap { overflow-x: auto; }
  .toolbar { padding: 1rem 1.5rem; display: flex; align-items: center; justify-content: space-between; gap: 1rem; border-bottom: 1px solid var(--border); }
  .search-box { display: flex; align-items: center; gap: 8px; background: var(--surface2); border: 1px solid var(--border); border-radius: 9px; padding: .45rem .9rem; flex: 1; max-width: 300px; }
  .search-box input { background: none; border: none; outline: none; color: var(--text); font-family: 'DM Sans', sans-serif; font-size: .88rem; width: 100%; }
  .search-icon { color: var(--muted); font-size: .9rem; }

  table { width: 100%; border-collapse: collapse; font-size: .88rem; }
  thead th { padding: .85rem 1.2rem; text-align: left; font-size: .73rem; text-transform: uppercase; letter-spacing: .08em; color: var(--muted); background: var(--surface2); border-bottom: 1px solid var(--border); white-space: nowrap; }
  tbody tr { border-bottom: 1px solid rgba(255,255,255,.04); transition: background .15s; }
  tbody tr:hover { background: rgba(0,178,255,.04); }
  tbody td { padding: .75rem 1.2rem; vertical-align: middle; }

  /* PHONE THUMB */
  .phone-thumb { width: 46px; height: 56px; object-fit: contain; border-radius: 6px; background: var(--surface2); border: 1px solid var(--border); flex-shrink: 0; cursor: pointer; transition: transform .2s, box-shadow .2s; }
  .phone-thumb:hover { transform: scale(1.1); box-shadow: 0 0 12px rgba(0,178,255,.3); }
  .phone-placeholder { width: 46px; height: 56px; border-radius: 6px; background: linear-gradient(135deg, rgba(0,178,255,.15), rgba(26,108,255,.15)); border: 1px solid rgba(0,178,255,.2); display: flex; align-items: center; justify-content: center; font-size: 1.3rem; flex-shrink: 0; cursor: pointer; transition: transform .2s; }
  .phone-placeholder:hover { transform: scale(1.1); }

  .model-link { font-weight: 500; color: var(--text); cursor: pointer; transition: color .15s; display: flex; align-items: center; gap: 6px; }
  .model-link:hover { color: var(--blue); }
  .model-link .arrow { font-size: .7rem; opacity: 0; transition: opacity .15s, transform .15s; }
  .model-link:hover .arrow { opacity: 1; transform: translateX(3px); }

  .color-dot { display: inline-flex; align-items: center; gap: 7px; }
  .color-swatch { width: 12px; height: 12px; border-radius: 50%; border: 2px solid rgba(255,255,255,.2); }
  .stock-pill { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: .78rem; font-weight: 500; }
  .stock-ok   { background: rgba(0,214,143,.12); color: var(--success); }
  .stock-warn { background: rgba(255,179,0,.12);  color: var(--warning); }
  .stock-low  { background: rgba(255,71,87,.12);  color: var(--danger); }
  .price-val { font-family: 'Syne', sans-serif; font-weight: 600; color: var(--blue); }
  .actions { display: flex; gap: 6px; }
  .empty-state { padding: 3rem; text-align: center; color: var(--muted); }
  .empty-icon { font-size: 2.5rem; margin-bottom: 1rem; opacity: .3; }

  /* ══════════════════════════════════════
     PRODUCT DETAIL MODAL
  ══════════════════════════════════════ */
  .modal-overlay {
    display: none; position: fixed; inset: 0; z-index: 500;
    background: rgba(5, 9, 20, 0.82);
    backdrop-filter: blur(12px);
    align-items: center; justify-content: center;
    padding: 1.5rem;
  }
  .modal-overlay.open { display: flex; animation: fadeIn .22s ease; }

  .modal {
    background: var(--surface);
    border: 1px solid rgba(0,178,255,0.2);
    border-radius: 20px;
    width: 100%; max-width: 780px;
    max-height: 90vh;
    overflow-y: auto;
    position: relative;
    box-shadow: 0 30px 80px rgba(0,0,0,.6), 0 0 60px rgba(0,178,255,.08);
    animation: slideUp .28s cubic-bezier(.22,1,.36,1);
  }

  .modal-close {
    position: absolute; top: 1.1rem; right: 1.1rem; z-index: 10;
    width: 34px; height: 34px; border-radius: 50%;
    background: var(--surface2); border: 1px solid var(--border);
    color: var(--muted); font-size: 1rem; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    transition: all .15s;
  }
  .modal-close:hover { background: rgba(255,71,87,.15); color: var(--danger); border-color: rgba(255,71,87,.3); }

  /* Top hero section with gradient */
  .modal-hero {
    display: grid; grid-template-columns: 240px 1fr;
    min-height: 220px; position: relative; overflow: hidden;
  }
  .modal-hero::before {
    content: ''; position: absolute; inset: 0;
    background: linear-gradient(135deg, rgba(0,102,204,.25) 0%, rgba(26,108,255,.1) 50%, transparent 100%);
    pointer-events: none;
  }

  .modal-photo-panel {
    display: flex; align-items: center; justify-content: center;
    padding: 1.8rem; background: rgba(0,0,0,.15);
    border-right: 1px solid var(--border);
  }
  .modal-phone-img {
    max-width: 160px; max-height: 190px; object-fit: contain;
    filter: drop-shadow(0 10px 30px rgba(0,178,255,.25));
    transition: transform .3s;
  }
  .modal:hover .modal-phone-img { transform: translateY(-4px); }
  .modal-phone-placeholder {
    font-size: 5rem; opacity: .25;
  }

  .modal-hero-info {
    padding: 1.8rem 2rem 1.8rem 1.8rem;
    display: flex; flex-direction: column; justify-content: center; gap: .5rem;
  }
  .modal-badge {
    display: inline-flex; align-items: center; gap: 6px;
    background: rgba(0,178,255,.12); border: 1px solid rgba(0,178,255,.25);
    color: var(--blue); font-size: .72rem; font-weight: 600;
    padding: 3px 10px; border-radius: 20px;
    letter-spacing: .05em; text-transform: uppercase;
    width: fit-content; margin-bottom: 4px;
  }
  .modal-model-name {
    font-family: 'Syne', sans-serif;
    font-size: 1.65rem; font-weight: 800;
    line-height: 1.15; letter-spacing: -0.02em;
    color: var(--text);
  }
  .modal-id { font-size: .78rem; color: var(--muted); margin-top: 2px; }

  /* Specs grid */
  .modal-specs {
    display: grid; grid-template-columns: repeat(4, 1fr);
    gap: 0; border-top: 1px solid var(--border);
  }
  .spec-item {
    padding: 1.3rem 1.4rem;
    border-right: 1px solid var(--border);
    position: relative;
  }
  .spec-item:last-child { border-right: none; }
  .spec-icon { font-size: 1.1rem; margin-bottom: 6px; }
  .spec-label { font-size: .7rem; color: var(--muted); text-transform: uppercase; letter-spacing: .08em; margin-bottom: 4px; }
  .spec-value { font-family: 'Syne', sans-serif; font-size: 1.15rem; font-weight: 700; color: var(--text); }
  .spec-value.price-color { color: var(--blue); font-size: 1.05rem; }
  .spec-value.stock-ok-c   { color: var(--success); }
  .spec-value.stock-warn-c  { color: var(--warning); }
  .spec-value.stock-low-c   { color: var(--danger); }

  /* Color swatch in modal */
  .modal-color-row { display: flex; align-items: center; gap: 8px; }
  .modal-swatch { width: 14px; height: 14px; border-radius: 50%; border: 2px solid rgba(255,255,255,.25); flex-shrink: 0; }

  /* Action bar at bottom of modal */
  .modal-actions {
    display: flex; gap: 10px; padding: 1.2rem 1.6rem;
    border-top: 1px solid var(--border);
    background: var(--surface2);
  }
  .modal-btn {
    display: inline-flex; align-items: center; justify-content: center; gap: 8px;
    padding: .62rem 1.3rem; border-radius: 9px; border: none; cursor: pointer;
    font-family: 'DM Sans', sans-serif; font-size: .88rem; font-weight: 500;
    transition: all .18s; text-decoration: none;
  }
  .modal-btn-edit {
    background: linear-gradient(135deg, var(--blue), var(--accent));
    color: #fff; box-shadow: 0 4px 16px rgba(0,178,255,.22);
  }
  .modal-btn-edit:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(0,178,255,.35); }
  .modal-btn-del {
    background: rgba(255,71,87,.1); color: var(--danger);
    border: 1px solid rgba(255,71,87,.28);
  }
  .modal-btn-del:hover { background: rgba(255,71,87,.2); }
  .modal-btn-close {
    background: var(--surface); color: var(--muted);
    border: 1px solid var(--border); margin-left: auto;
  }
  .modal-btn-close:hover { color: var(--text); }

  /* Value bar (stock indicator) */
  .stock-bar-wrap { margin-top: 6px; }
  .stock-bar-bg { height: 4px; background: rgba(255,255,255,.06); border-radius: 4px; overflow: hidden; }
  .stock-bar-fill { height: 100%; border-radius: 4px; transition: width .6s ease; }

  footer { text-align: center; padding: 1.5rem; color: var(--muted); font-size: .78rem; border-top: 1px solid var(--border); margin-top: 2rem; }

  @keyframes slideIn  { from { opacity: 0; transform: translateY(-8px); } to { opacity: 1; transform: translateY(0); } }
  @keyframes fadeIn   { from { opacity: 0; } to { opacity: 1; } }
  @keyframes slideUp  { from { opacity: 0; transform: translateY(30px) scale(.97); } to { opacity: 1; transform: translateY(0) scale(1); } }

  @media (max-width: 900px) {
    .main-grid { grid-template-columns: 1fr; }
    .stats { grid-template-columns: repeat(2, 1fr); }
    .modal-hero { grid-template-columns: 1fr; }
    .modal-photo-panel { border-right: none; border-bottom: 1px solid var(--border); padding: 1.4rem; min-height: 160px; }
    .modal-specs { grid-template-columns: repeat(2, 1fr); }
    .spec-item:nth-child(2) { border-right: none; }
    .spec-item:nth-child(3), .spec-item:nth-child(4) { border-top: 1px solid var(--border); }
  }
</style>
</head>
<body>

<!-- ══ PRODUCT DETAIL MODAL ══ -->
<div class="modal-overlay" id="detailModal" onclick="handleOverlayClick(event)">
  <div class="modal" id="modalBox">
    <button class="modal-close" onclick="closeModal()" title="Close">✕</button>

    <!-- Hero: photo + name -->
    <div class="modal-hero">
      <div class="modal-photo-panel">
        <img src="" id="modalPhoto" class="modal-phone-img" alt="Phone" style="display:none">
        <div class="modal-phone-placeholder" id="modalPlaceholder">📱</div>
      </div>
      <div class="modal-hero-info">
        <div class="modal-badge" id="modalBadge">📱 Samsung</div>
        <div class="modal-model-name" id="modalModel">—</div>
        <div class="modal-id" id="modalIdLine">ID #—</div>
      </div>
    </div>

    <!-- Specs row -->
    <div class="modal-specs">
      <div class="spec-item">
        <div class="spec-icon">💾</div>
        <div class="spec-label">Storage</div>
        <div class="spec-value" id="modalGb">—</div>
      </div>
      <div class="spec-item">
        <div class="spec-icon">🎨</div>
        <div class="spec-label">Color</div>
        <div class="spec-value" id="modalColor">—</div>
      </div>
      <div class="spec-item">
        <div class="spec-icon">💰</div>
        <div class="spec-label">Price</div>
        <div class="spec-value price-color" id="modalPrice">—</div>
      </div>
      <div class="spec-item">
        <div class="spec-icon">📦</div>
        <div class="spec-label">Stock</div>
        <div class="spec-value" id="modalStocks">—</div>
        <div class="stock-bar-wrap">
          <div class="stock-bar-bg"><div class="stock-bar-fill" id="modalStockBar" style="width:0%;background:var(--success)"></div></div>
        </div>
      </div>
    </div>

    <!-- Action buttons -->
    <div class="modal-actions">
      <a href="#" class="modal-btn modal-btn-edit" id="modalEditBtn">✏️ Edit Product</a>
      <form method="POST" id="modalDeleteForm" onsubmit="return confirm('Delete this product?');" style="display:inline">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" id="modalDeleteId" value="">
        <button type="submit" class="modal-btn modal-btn-del">🗑 Delete</button>
      </form>
      <button class="modal-btn modal-btn-close" onclick="closeModal()">✕ Close</button>
    </div>
  </div>
</div>

<!-- HEADER -->
<header>
  <div class="logo">
    <div class="logo-mark">S</div>
    <div class="logo-text"><span>Samsung</span> Inventory</div>
  </div>
  <div class="header-right">
    <span class="header-badge">● Live</span>
    <span class="header-time" id="clock"></span>
  </div>
</header>

<div class="wrapper">
  <div class="page-title">
    <h1>Inventory <span>Dashboard</span></h1>
    <p>Manage your Samsung product stock in real time</p>
  </div>

  <!-- STATS -->
  <div class="stats">
    <div class="stat-card"><div class="stat-icon">📦</div><div class="stat-label">Total Models</div><div class="stat-value blue"><?= $total_products ?></div></div>
    <div class="stat-card"><div class="stat-icon">🗂️</div><div class="stat-label">Total Units</div><div class="stat-value"><?= number_format($total_stock) ?></div></div>
    <div class="stat-card"><div class="stat-icon">💰</div><div class="stat-label">Inventory Value</div><div class="stat-value success">₱<?= number_format($total_value, 0) ?></div></div>
    <div class="stat-card"><div class="stat-icon">⚠️</div><div class="stat-label">Low Stock Items</div><div class="stat-value warning"><?= $low_stock ?></div></div>
  </div>

  <?php if ($message): ?>
  <div class="alert <?= $message_type ?>"><?= $message_type === 'success' ? '✔' : '✖' ?> <?= htmlspecialchars($message) ?></div>
  <?php endif; ?>

  <div class="main-grid">

    <!-- FORM -->
    <div class="card">
      <div class="card-header">
        <div class="dot"></div>
        <?= $edit_item ? 'Edit Product' : 'Add New Product' ?>
      </div>
      <div class="card-body">
        <form method="POST" action="index.php<?= $edit_item ? '?edit_id='.$edit_item['id'] : '' ?>" enctype="multipart/form-data">
          <input type="hidden" name="action" value="<?= $edit_item ? 'edit' : 'add' ?>">
          <?php if ($edit_item): ?>
            <input type="hidden" name="id" value="<?= $edit_item['id'] ?>">
          <?php endif; ?>
          <div class="form-group">
            <label>Model Name</label>
            <input type="text" name="model" placeholder="e.g. Galaxy S24 Ultra" value="<?= htmlspecialchars($edit_item['Model'] ?? '') ?>" required>
          </div>
          <div class="form-group">
            <label>Storage (GB)</label>
            <select name="gb">
              <?php foreach ([64,128,256,512,1024] as $g): ?>
                <option value="<?= $g ?>" <?= ($edit_item['Gb'] ?? '') == $g ? 'selected' : '' ?>><?= $g ?>GB</option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Color</label>
            <input type="text" name="color" placeholder="e.g. Phantom Black" value="<?= htmlspecialchars($edit_item['Color'] ?? '') ?>" required>
          </div>
          <div class="form-group">
            <label>Price (₱)</label>
            <input type="number" name="price" step="0.01" placeholder="0.00" value="<?= $edit_item['price'] ?? '' ?>" required>
          </div>
          <div class="form-group">
            <label>Stocks</label>
            <input type="number" name="stocks" placeholder="0" value="<?= $edit_item['Stocks'] ?? '' ?>" required>
          </div>
          <div class="form-group">
            <label>Phone Photo</label>
            <?php if ($edit_item && !empty($edit_item['photo'])): ?>
            <div class="existing-photo">
              <div class="existing-label">Current photo:</div>
              <img src="uploads/<?= htmlspecialchars($edit_item['photo']) ?>" alt="Current photo">
            </div>
            <?php endif; ?>
            <div class="upload-area" id="uploadArea">
              <input type="file" name="photo" id="photoInput" accept="image/jpeg,image/png,image/webp" onchange="previewPhoto(this)">
              <div class="upload-icon">📷</div>
              <div class="upload-label"><span>Click to upload</span> or drag & drop<br>JPG, PNG, WebP</div>
            </div>
            <div class="photo-preview-wrap" id="previewWrap">
              <img src="" id="photoPreview" alt="Preview">
            </div>
          </div>
          <button type="submit" class="btn btn-primary"><?= $edit_item ? '💾 Update Product' : '＋ Add Product' ?></button>
          <?php if ($edit_item): ?>
            <a href="index.php" class="btn btn-cancel">✕ Cancel</a>
          <?php endif; ?>
        </form>
      </div>
    </div>

    <!-- TABLE -->
    <div class="card">
      <div class="toolbar">
        <div style="font-family:'Syne',sans-serif;font-weight:700;font-size:.95rem;display:flex;align-items:center;gap:10px;">
          <div style="width:8px;height:8px;border-radius:50%;background:var(--blue);box-shadow:0 0 8px var(--blue)"></div>
          Product List
        </div>
        <form method="GET" style="display:flex;gap:8px;flex:1;justify-content:flex-end;">
          <div class="search-box">
            <span class="search-icon">🔍</span>
            <input type="text" name="search" placeholder="Search model or color…" value="<?= htmlspecialchars($search) ?>">
          </div>
          <button type="submit" class="btn btn-edit btn-sm">Search</button>
          <?php if ($search): ?>
            <a href="index.php" class="btn btn-sm" style="background:var(--surface2);color:var(--muted)">Clear</a>
          <?php endif; ?>
        </form>
      </div>

      <div class="table-wrap">
        <?php if ($result && $result->num_rows > 0): ?>
        <table>
          <thead>
            <tr>
              <th>#</th>
              <th>Photo</th>
              <th>Model</th>
              <th>Storage</th>
              <th>Color</th>
              <th>Price</th>
              <th>Stocks</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($row = $result->fetch_assoc()):
              $stocks     = (int)$row['Stocks'];
              $stockClass = $stocks > 10 ? 'stock-ok' : ($stocks > 5 ? 'stock-warn' : 'stock-low');
              $colorMap   = ['black'=>'#222','white'=>'#eee','blue'=>'#1a6cff','green'=>'#00d68f','gray'=>'#888','silver'=>'#b0b8c4','red'=>'#ff4757','gold'=>'#ffb300','violet'=>'#8e44ad','phantom'=>'#1a1a2e','titanium'=>'#8b8fa8'];
              $cKey       = strtolower(explode(' ', $row['Color'])[0]);
              $swatch     = $colorMap[$cKey] ?? '#555';
              $photoPath  = (!empty($row['photo']) && file_exists("uploads/" . $row['photo'])) ? "uploads/" . htmlspecialchars($row['photo']) : '';

              // Build JSON-safe data for modal
              $rowJson = json_encode([
                'id'    => $row['id'],
                'model' => $row['Model'],
                'gb'    => $row['Gb'],
                'color' => $row['Color'],
                'price' => $row['price'],
                'stocks'=> $row['Stocks'],
                'photo' => $photoPath,
                'swatch'=> $swatch,
              ]);
            ?>
            <tr>
              <td style="color:var(--muted)"><?= $row['id'] ?></td>
              <td>
                <?php if ($photoPath): ?>
                  <img src="<?= $photoPath ?>" alt="<?= htmlspecialchars($row['Model']) ?>" class="phone-thumb" onclick='openModal(<?= $rowJson ?>)' title="View details">
                <?php else: ?>
                  <div class="phone-placeholder" onclick='openModal(<?= $rowJson ?>)' title="View details">📱</div>
                <?php endif; ?>
              </td>
              <td>
                <div class="model-link" onclick='openModal(<?= $rowJson ?>)'>
                  <?= htmlspecialchars($row['Model']) ?>
                  <span class="arrow">→</span>
                </div>
              </td>
              <td><?= $row['Gb'] ?> GB</td>
              <td>
                <div class="color-dot">
                  <div class="color-swatch" style="background:<?= $swatch ?>"></div>
                  <?= htmlspecialchars($row['Color']) ?>
                </div>
              </td>
              <td><span class="price-val">₱<?= number_format($row['price'], 2) ?></span></td>
              <td><span class="stock-pill <?= $stockClass ?>"><?= $stocks ?> units</span></td>
              <td>
                <div class="actions">
                  <a href="index.php?edit_id=<?= $row['id'] ?>" class="btn btn-edit btn-sm">✏️ Edit</a>
                  <form method="POST" onsubmit="return confirm('Delete this product?');" style="display:inline">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                    <button type="submit" class="btn btn-del btn-sm">🗑 Del</button>
                  </form>
                </div>
              </td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
        <?php else: ?>
        <div class="empty-state">
          <div class="empty-icon">📭</div>
          <p><?= $search ? 'No products matched your search.' : 'No products yet. Add your first one!' ?></p>
        </div>
        <?php endif; ?>
      </div>
    </div>

  </div>
</div>

<footer>Samsung Inventory System &nbsp;·&nbsp; <?= date('Y') ?> &nbsp;·&nbsp; Built with PHP & MySQL</footer>

<script>
// Clock
function updateClock() {
  const now = new Date();
  document.getElementById('clock').textContent =
    now.toLocaleTimeString('en-PH', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
}
updateClock();
setInterval(updateClock, 1000);

// Alert auto-dismiss
const alertEl = document.querySelector('.alert');
if (alertEl) setTimeout(() => alertEl.style.display = 'none', 4000);

// Form photo preview
function previewPhoto(input) {
  const wrap = document.getElementById('previewWrap');
  const img  = document.getElementById('photoPreview');
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = e => { img.src = e.target.result; wrap.style.display = 'block'; };
    reader.readAsDataURL(input.files[0]);
    document.getElementById('uploadArea').querySelector('.upload-label').innerHTML =
      '<span style="color:var(--success)">✔ ' + input.files[0].name + '</span>';
  }
}

// ── MODAL ──────────────────────────────────
const colorMap = {
  black:'#222', white:'#eee', blue:'#1a6cff', green:'#00d68f',
  gray:'#888', silver:'#b0b8c4', red:'#ff4757', gold:'#ffb300',
  violet:'#8e44ad', phantom:'#1a1a2e', titanium:'#8b8fa8'
};

function openModal(data) {
  // Photo
  const photoEl  = document.getElementById('modalPhoto');
  const placeholderEl = document.getElementById('modalPlaceholder');
  if (data.photo) {
    photoEl.src = data.photo;
    photoEl.style.display = 'block';
    placeholderEl.style.display = 'none';
  } else {
    photoEl.style.display = 'none';
    placeholderEl.style.display = 'block';
  }

  // Name & ID
  document.getElementById('modalModel').textContent = data.model;
  document.getElementById('modalIdLine').textContent = 'Product ID #' + data.id;
  document.getElementById('modalBadge').textContent  = '📱 Samsung Device';

  // GB
  document.getElementById('modalGb').textContent = data.gb + ' GB';

  // Color with swatch
  const cKey = data.color.split(' ')[0].toLowerCase();
  const sw   = colorMap[cKey] || data.swatch || '#555';
  document.getElementById('modalColor').innerHTML =
    '<div class="modal-color-row"><div class="modal-swatch" style="background:' + sw + '"></div>' + data.color + '</div>';

  // Price
  const price = parseFloat(data.price);
  document.getElementById('modalPrice').textContent = '₱' + price.toLocaleString('en-PH', {minimumFractionDigits:2, maximumFractionDigits:2});

  // Stocks + color class + bar
  const s = parseInt(data.stocks);
  const stockEl  = document.getElementById('modalStocks');
  const barEl    = document.getElementById('modalStockBar');
  stockEl.textContent = s + ' units';
  stockEl.className   = 'spec-value ' + (s > 10 ? 'stock-ok-c' : s > 5 ? 'stock-warn-c' : 'stock-low-c');
  const barColor = s > 10 ? 'var(--success)' : s > 5 ? 'var(--warning)' : 'var(--danger)';
  const barPct   = Math.min(100, Math.round((s / Math.max(s, 100)) * 100));
  barEl.style.width      = barPct + '%';
  barEl.style.background = barColor;

  // Edit link
  document.getElementById('modalEditBtn').href = 'index.php?edit_id=' + data.id;

  // Delete form
  document.getElementById('modalDeleteId').value = data.id;

  // Show
  document.getElementById('detailModal').classList.add('open');
  document.body.style.overflow = 'hidden';
}

function closeModal() {
  document.getElementById('detailModal').classList.remove('open');
  document.body.style.overflow = '';
}

function handleOverlayClick(e) {
  if (e.target === document.getElementById('detailModal')) closeModal();
}

document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });
</script>

</body>
</html>
<?php $conn->close(); ?>