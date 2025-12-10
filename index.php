<?php
// index.php
require_once 'core/init.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    // Jika belum, tendang ke halaman login
    header('Location: login.php');
    exit;
}

// Ambil data dari session
$role = $_SESSION['role'];
$nama = $_SESSION['nama'];

// Arahkan berdasarkan role
if ($role == 'owner') {
    // Jika owner, arahkan ke dashboard owner
    header('Location: pages/owner/dashboard.php');
    exit;
} elseif ($role == 'pekerja') {
    // Jika pekerja, arahkan ke dashboard pekerja
    header('Location: pages/pekerja/dashboard.php');
    exit;
} else {
    // Jika role tidak jelas (seharusnya tidak terjadi), logout saja
    header('Location: logout.php');
    exit;
}
?>