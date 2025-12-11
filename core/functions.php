<?php
// core/functions.php

// ================================================================
// KONFIGURASI ENKRIPSI (SESUAI MANUAL PDF)
// ================================================================

// KUNCI: Wajib 16 Karakter persis agar sesuai dengan matriks 4x4
// Ubah string ini jika ingin mengganti kunci, tapi panjangnya harus tetap 16.
define('AES_KEY', 'SMKHARAPANBANGSA'); 

// METODE: AES-128-ECB (Electronic Codebook)
define('AES_METHOD', 'AES-128-ECB');

/**
 * Fungsi Enkripsi Data
 * Mengubah data asli (misal: "14000") menjadi Hexadecimal (misal: "83AF...")
 * Menggunakan Padding PKCS#7 secara otomatis oleh OpenSSL.
 */
function encrypt_data($data) {
    // 1. Pastikan data dikonversi ke string
    $data = (string)$data;
    
    // 2. Ambil Kunci Mentah (Raw Key)
    $key = AES_KEY;
    
    // 3. Proses Enkripsi AES-128-ECB
    // OPENSSL_RAW_DATA: Menghasilkan byte murni (bukan base64)
    // OpenSSL secara default menambahkan Padding PKCS#7 jika data < 16 byte
    $encrypted_raw = openssl_encrypt($data, AES_METHOD, $key, OPENSSL_RAW_DATA);
    
    // 4. Konversi ke HEXADECIMAL & Huruf Besar
    // Ini agar outputnya sama persis dengan tabel perhitungan manual
    return strtoupper(bin2hex($encrypted_raw));
}

/**
 * Fungsi Dekripsi Data
 * Mengubah Hexadecimal (misal: "83AF...") kembali menjadi data asli.
 */
function decrypt_data($hex_data) {
    // 1. Ambil Kunci Mentah
    $key = AES_KEY;
    
    // 2. Kembalikan Hex ke Binary
    // Jika input bukan hex yang valid, fungsi ini mungkin error/kosong
    $binary_data = @hex2bin($hex_data);
    
    if ($binary_data === false) {
        return 0; // Gagal konversi hex
    }

    // 3. Proses Dekripsi
    // OpenSSL otomatis membuang padding PKCS#7 setelah dekripsi
    $decrypted = openssl_decrypt($binary_data, AES_METHOD, $key, OPENSSL_RAW_DATA);
    
    // 4. Validasi Hasil
    // Kembalikan 0 jika gagal dekripsi (kunci salah atau data rusak)
    return $decrypted === false ? 0 : $decrypted;
}
?>