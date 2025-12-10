<?php
// Memanggil config DB dan autoload Composer
require_once 'core/init.php';

// Menggunakan namespace Dompdf
use Dompdf\Dompdf;
use Dompdf\Options;

// Cek apakah ID transaksi ada
if (!isset($_GET['id'])) {
    die("Error: ID Transaksi tidak ditemukan.");
}

$transaksi_id = (int) $_GET['id'];

// Ambil data transaksi lengkap dari DB
$stmt = $db->prepare(
    "SELECT 
        t.kode_barcode, t.waktu_masuk,
        k.plat_nomor, k.jenis,
        petugas_masuk.nama AS nama_petugas_masuk
     FROM transaksi_parkir t
     JOIN kendaraan k ON t.id_kendaraan = k.id
     JOIN users petugas_masuk ON t.id_petugas_masuk = petugas_masuk.id
     WHERE t.id = ?"
);
$stmt->bind_param("i", $transaksi_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("Error: Data transaksi tidak ditemukan.");
}

$data = $result->fetch_assoc();
$stmt->close();
$db->close();

// --- Mulai Membuat HTML untuk PDF ---
$html = "
<!DOCTYPE html>
<html>
<head>
    <meta charset='utf-8'>
    <title>Tiket Parkir</title>
    <style>
        body { 
            font-family: 'Courier New', Courier, monospace; 
            font-size: 10pt; 
            color: #333;
        }
        .container { 
            width: 80mm; /* Lebar kertas thermal */
            padding: 5px; 
        }
        .header { 
            text-align: center; 
            font-size: 12pt;
            font-weight: bold;
            border-bottom: 1px dashed #000;
            padding-bottom: 10px;
            margin-bottom: 10px;
        }
        .info { width: 100%; margin-bottom: 10px; }
        .info td { padding: 3px 0; }
        .info .label { width: 35%; }
        .info .value { width: 65%; font-weight: bold; }
        
        .barcode-area {
            text-align: center;
            padding: 10px 0;
            margin: 10px 0;
            border-top: 1px dashed #000;
            border-bottom: 1px dashed #000;
        }
        .barcode-text {
            font-size: 14pt;
            font-weight: bold;
            letter-spacing: 2px;
        }
        
        .footer { 
            text-align: center; 
            font-size: 9pt; 
            margin-top: 15px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            TIKET MASUK<br>
            ParkirSistem
        </div>
        
        <table class='info'>
            <tr>
                <td class'label'>Plat Nomor:</td>
                <td class='value'>" . htmlspecialchars($data['plat_nomor']) . "</td>
            </tr>
            <tr>
                <td class='label'>Jenis:</td>
                <td class'value'>" . ucfirst($data['jenis']) . "</td>
            </tr>
            <tr>
                <td class='label'>Waktu Masuk:</td>
                <td class='value'>" . date('d/m/Y H:i:s', strtotime($data['waktu_masuk'])) . "</td>
            </tr>
            <tr>
                <td class='label'>Petugas:</td>
                <td class'value'>" . htmlspecialchars($data['nama_petugas_masuk']) . "</td>
            </tr>
        </table>
        
        <div class='barcode-area'>
            <div class'barcode-text'>" . htmlspecialchars($data['kode_barcode']) . "</div>
            </div>
        
        <div class'footer'>
            MOHON SIMPAN TIKET INI<br>
            UNTUK PROSES PEMBAYARAN SAAT KELUAR
        </div>
    </div>
</body>
</html>
";

// --- Proses Render PDF ---
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'Courier');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);

// Ukuran kertas struk thermal 80mm
$customPaper = array(0, 0, 227, 425);
$dompdf->setPaper($customPaper);

// Render HTML sebagai PDF
$dompdf->render();

// Tampilkan PDF di browser
$dompdf->stream("tiket-parkir-" . $data['kode_barcode'] . ".pdf", ["Attachment" => 0]);

exit;
?>