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
        t.*, 
        k.plat_nomor, k.jenis,
        petugas_masuk.nama AS nama_petugas_masuk,
        petugas_keluar.nama AS nama_petugas_keluar
     FROM transaksi_parkir t
     JOIN kendaraan k ON t.id_kendaraan = k.id
     JOIN users petugas_masuk ON t.id_petugas_masuk = petugas_masuk.id
     LEFT JOIN users petugas_keluar ON t.id_petugas_keluar = petugas_keluar.id
     WHERE t.id = ? AND t.status = 'keluar'"
);
$stmt->bind_param("i", $transaksi_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("Error: Data transaksi tidak ditemukan atau belum selesai.");
}

$data = $result->fetch_assoc();
$stmt->close();
$db->close();

// --- Logika Perhitungan Durasi (Lagi) ---
$waktu_masuk = new DateTime($data['waktu_masuk']);
$waktu_keluar = new DateTime($data['waktu_keluar']);
$durasi = $waktu_keluar->diff($waktu_masuk);
$durasi_format = $durasi->d . ' hari, ' . $durasi->h . ' jam, ' . $durasi->i . ' m';

// --- Mulai Membuat HTML untuk PDF ---
$html = "
<!DOCTYPE html>
<html>
<head>
    <meta charset='utf-8'>
    <title>Struk Parkir</title>
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
        .info td { padding: 2px 0; }
        .info .label { width: 40%; }
        .info .value { width: 60%; }
        .total { 
            font-size: 14pt; 
            font-weight: bold; 
            text-align: center;
            border-top: 1px dashed #000;
            border-bottom: 1px dashed #000;
            padding: 10px 0;
            margin: 10px 0;
        }
        .footer { 
            text-align: center; 
            font-size: 9pt; 
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            ParkirSistem
        </div>
        
        <table class='info'>
            <tr>
                <td class='label'>Kode Tiket:</td>
                <td class='value'>" . htmlspecialchars($data['kode_barcode']) . "</td>
            </tr>
            <tr>
                <td class='label'>Plat Nomor:</td>
                <td class='value'><strong>" . htmlspecialchars($data['plat_nomor']) . "</strong></td>
            </tr>
            <tr>
                <td class='label'>Jenis:</td>
                <td class='value'>" . ucfirst($data['jenis']) . "</td>
            </tr>
            <tr><td colspan='2'><hr></td></tr>
            <tr>
                <td class='label'>Masuk:</td>
                <td class='value'>" . date('d/m/y H:i', strtotime($data['waktu_masuk'])) . "</td>
            </tr>
            <tr>
                <td class='label'>Keluar:</td>
                <td class='value'>" . date('d/m/y H:i', strtotime($data['waktu_keluar'])) . "</td>
            </tr>
            <tr>
                <td class='label'>Durasi:</td>
                <td class='value'>" . $durasi_format . "</td>
            </tr>
            <tr><td colspan='2'><hr></td></tr>
            <tr>
                <td class='label'>Petugas:</td>
                <td class='value'>" . htmlspecialchars($data['nama_petugas_keluar']) . "</td>
            </tr>
        </table>
        
        <div class='total'>
            TOTAL BAYAR: Rp " . number_format($data['biaya'], 0, ',', '.') . "
        </div>
        
        <div class='footer'>
            Terima kasih atas kunjungan Anda.<br>
            --- Parkir Aman & Nyaman ---
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

// (Opsional) Tentukan ukuran kertas. 80mm adalah lebar struk standar.
// Ukuran dalam points (1mm = 2.83465 pt). 80mm = 227pt.
// Kita buat panjangnya 150mm = 425pt.
$customPaper = array(0, 0, 227, 425);
$dompdf->setPaper($customPaper);

// Render HTML sebagai PDF
$dompdf->render();

// Tampilkan PDF di browser
// Setting "Attachment" ke 0 (false) agar PDF tampil di tab, bukan ter-download otomatis
$dompdf->stream("struk-parkir-" . $data['kode_barcode'] . ".pdf", ["Attachment" => 0]);

exit;
?>