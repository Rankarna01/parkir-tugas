<?php
session_start();
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$hash = '';
$verify_result = null;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // basic CSRF check
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errors[] = 'Invalid request token.';
    } else {
        if (isset($_POST['action']) && $_POST['action'] === 'generate') {
            $pwd = trim($_POST['password'] ?? '');
            if ($pwd === '') {
                $errors[] = 'Password tidak boleh kosong.';
            } else {
                // Generate password hash using PHP password_hash (recommended)
                $hash = password_hash($pwd, PASSWORD_DEFAULT);
            }
        }

        if (isset($_POST['action']) && $_POST['action'] === 'verify') {
            $pwdv = trim($_POST['password_verify'] ?? '');
            $hashv = trim($_POST['hash_verify'] ?? '');
            if ($pwdv === '' || $hashv === '') {
                $errors[] = 'Masukkan password dan hash untuk verifikasi.';
            } else {
                $verify_result = password_verify($pwdv, $hashv);
            }
        }
    }
}
?><!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Generator Password Hash</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; background:#f4f6f8; color:#222; padding:30px; }
        .card{background:#fff;border-radius:8px;box-shadow:0 6px 18px rgba(0,0,0,0.06);padding:20px;max-width:800px;margin:0 auto}
        input[type=text], input[type=password], textarea{width:100%;padding:10px;border:1px solid #ddd;border-radius:4px;margin-top:8px}
        button{background:#007bff;color:#fff;padding:10px 14px;border:none;border-radius:6px;cursor:pointer}
        .muted{color:#666;font-size:13px}
        .row{display:grid;grid-template-columns:1fr 1fr;gap:16px}
        @media(max-width:700px){.row{grid-template-columns:1fr}}
        .result{background:#f8f9fb;padding:12px;border-radius:6px;border:1px dashed #ccd}
        .errors{color:#b00020}
    </style>
</head>
<body>
<div class="card">
    <h2>Generator Password Hash</h2>
    <p class="muted">Masukkan password lalu klik <strong>Generate</strong> untuk membuat hash yang aman (menggunakan <code>password_hash</code> PHP).</p>

    <?php if ($errors): ?>
        <div class="errors">
            <ul>
                <?php foreach ($errors as $e): ?>
                    <li><?php echo htmlentities($e); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" onsubmit="return true;">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

        <label><strong>Password</strong>
            <input type="password" name="password" placeholder="Masukkan password untuk di-hash">
        </label>
        <div style="margin-top:10px">
            <button type="submit" name="action" value="generate">Generate Hash</button>
        </div>
    </form>

    <?php if ($hash): ?>
        <h3 style="margin-top:18px">Hash yang dihasilkan</h3>
        <div class="result">
            <textarea id="generatedHash" rows="3" readonly><?php echo htmlentities($hash); ?></textarea>
            <div style="margin-top:8px">
                <button onclick="copyHash()">Salin Hash</button>
                <span class="muted" style="margin-left:12px">Gunakan <code>password_verify()</code> untuk memeriksa password saat login.</span>
            </div>
        </div>
    <?php endif; ?>

    <hr style="margin:20px 0">

    <h3>Verifikasi Password</h3>
    <p class="muted">Masukkan password dan hash untuk memeriksa apakah cocok.</p>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        <label><strong>Password</strong>
            <input type="password" name="password_verify" placeholder="Password plain text">
        </label>
        <label style="margin-top:10px"><strong>Hash</strong>
            <textarea name="hash_verify" rows="3" placeholder="Paste hash di sini"></textarea>
        </label>
        <div style="margin-top:10px">
            <button type="submit" name="action" value="verify">Verify</button>
        </div>
    </form>

    <?php if ($verify_result !== null): ?>
        <div style="margin-top:12px">
            <?php if ($verify_result): ?>
                <div class="result">Hasil: <strong>Match ✅</strong> — password cocok dengan hash.</div>
            <?php else: ?>
                <div class="result">Hasil: <strong>Tidak cocok ❌</strong> — password tidak cocok dengan hash.</div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <hr style="margin:20px 0">
    <p class="muted">Tip keamanan: simpan hash di database, jangan menyimpan password plain-text. Gunakan HTTPS pada server produksi.</p>
</div>

<script>
function copyHash(){
    const ta = document.getElementById('generatedHash');
    if (!ta) return;
    ta.select();
    document.execCommand('copy');
    alert('Hash disalin ke clipboard');
}
</script>
</body>
</html>
