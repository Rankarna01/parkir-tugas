<?php
// logout.php
require_once 'core/init.php';

// Hancurkan semua data session
session_unset();
session_destroy();

// Redirect ke halaman login
header('Location: login.php');
exit;
?>