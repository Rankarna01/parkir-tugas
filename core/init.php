<?php
// core/init.php

// Mulai session
if (!session_id()) {
    session_start();
}

// Muat file konfigurasi
require_once 'config.php';
require_once __DIR__ . '/../vendor/autoload.php';

// Muat fungsi-fungsi umum (nanti kita isi)
require_once 'functions.php';

// Setting timezone
date_default_timezone_set('Asia/Jakarta');
?>