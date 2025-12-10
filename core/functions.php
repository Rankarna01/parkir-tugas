<?php
// core/functions.php

define('AES_KEY', 'KunciRahasiaParkir2025!'); 
define('AES_METHOD', 'AES-128-ECB');

function encrypt_data($data) {
    // Pastikan data dikonversi ke string dulu
    $data = (string)$data;
    
    $key = hash('sha256', AES_KEY, true);
    $encrypted = openssl_encrypt($data, AES_METHOD, substr($key, 0, 16));
    
    // Tambahkan base64_encode untuk keamanan karakter database
    return base64_encode($encrypted);
}

function decrypt_data($encrypted_data) {
    $key = hash('sha256', AES_KEY, true);
    
    // Decode dulu sebelum didekripsi
    $decoded = base64_decode($encrypted_data);
    
    $decrypted = openssl_decrypt($decoded, AES_METHOD, substr($key, 0, 16));
    
    // Kembalikan 0 jika gagal dekripsi
    return $decrypted === false ? 0 : $decrypted;
}
?>