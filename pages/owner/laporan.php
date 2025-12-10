<?php
//=========================================
// LOGIKA PHP (BAGIAN ATAS)
//=========================================
$page_title = "Laporan Keuangan";
require_once '../../core/init.php'; // Pastikan init.php memuat functions.php yang berisi encrypt_data/decrypt_data

// Menggunakan namespace Dompdf
use Dompdf\Dompdf;
use Dompdf\Options;

// Keamanan Halaman
if ($_SESSION['role'] != 'owner') {
    header('Location: ../../login.php');
    exit;
}

// -----------------------------------------------------------
// FITUR BARU: TOGGLE MODE TAMPILAN (ENKRIPSI <-> DEKRIPSI)
// -----------------------------------------------------------
// Logika ini menangani tombol "Mata" untuk melihat data asli atau menyembunyikannya
if (isset($_GET['toggle_mode'])) {
    // Balikkan status saat ini (jika true jadi false, jika false jadi true)
    $_SESSION['show_decrypted'] = !($_SESSION['show_decrypted'] ?? false);
    header("Location: laporan.php"); // Refresh halaman agar URL bersih
    exit;
}

// Cek status saat ini (Default: False / Tetap Terenkripsi jika belum diklik)
$is_decrypted_mode = $_SESSION['show_decrypted'] ?? false;


// --- LOGIKA GENERATE & ARSIP LAPORAN (BARU) ---
if (isset($_POST['generate_laporan'])) {
    $bulan = $_POST['bulan']; // Format: YYYY-MM
    
    // 1. Tentukan Range Tanggal Awal & Akhir Bulan
    $sql_start_gen = "$bulan-01 00:00:00";
    $sql_end_gen   = date("Y-m-t 23:59:59", strtotime($sql_start_gen));

    // 2. Hitung Total Pendapatan (Real) dari Transaksi
    $stmt_calc = $db->prepare("SELECT SUM(biaya) AS total FROM transaksi_parkir WHERE status = 'keluar' AND waktu_keluar BETWEEN ? AND ?");
    $stmt_calc->bind_param("ss", $sql_start_gen, $sql_end_gen);
    $stmt_calc->execute();
    $total_pendapatan_gen = $stmt_calc->get_result()->fetch_assoc()['total'] ?? 0;
    $stmt_calc->close();

    // 3. Hitung Pengeluaran & Laba (Opsional/Dummy)
    $total_pengeluaran_gen = 0; 
    $laba_bersih = $total_pendapatan_gen - $total_pengeluaran_gen;

    // -----------------------------------------------------------
    // PROSES ENKRIPSI (AES-128) SEBELUM SIMPAN KE DB
    // -----------------------------------------------------------
    // Kita mengenkripsi angka asli menjadi kode acak
    $enc_pendapatan  = encrypt_data($total_pendapatan_gen);
    $enc_pengeluaran = encrypt_data($total_pengeluaran_gen);
    $enc_laba        = encrypt_data($laba_bersih);
    // -----------------------------------------------------------

    // 4. Cek Duplikasi Laporan
    $nama_periode = date("F Y", strtotime($sql_start_gen)); // Contoh: October 2025
    
    $cek_lap = $db->query("SELECT id FROM laporan_keuangan WHERE periode = '$nama_periode'");
    if ($cek_lap->num_rows > 0) {
        $_SESSION['error_message'] = "Laporan untuk periode $nama_periode sudah pernah dibuat!";
    } else {
        // 5. Simpan Data TERENKRIPSI ke Database
        $stmt_save = $db->prepare("INSERT INTO laporan_keuangan (periode, total_pendapatan, total_pengeluaran, laba_bersih, tanggal_dibuat) VALUES (?, ?, ?, ?, NOW())");
        $stmt_save->bind_param("ssss", $nama_periode, $enc_pendapatan, $enc_pengeluaran, $enc_laba);
        
        if ($stmt_save->execute()) {
            $_SESSION['success_message'] = "Laporan $nama_periode berhasil disimpan. Data keuangan telah diamankan dengan enkripsi AES-128.";
        } else {
            $_SESSION['error_message'] = "Gagal menyimpan laporan: " . $db->error;
        }
        $stmt_save->close();
    }
    
    header("Location: laporan.php");
    exit;
}


// --- LOGIKA FILTER TAMPILAN ---
$filter_type = $_GET['filter_type'] ?? 'harian';
$laporan_title = '';
$start_datetime_val = '';
$end_datetime_val = '';

switch ($filter_type) {
    case 'mingguan':
        $sql_start = date('Y-m-d 00:00:00', strtotime('monday this week'));
        $sql_end = date('Y-m-d 23:59:59', strtotime('sunday this week'));
        $laporan_title = 'Laporan Mingguan';
        break;
    case 'bulanan':
        $sql_start = date('Y-m-01 00:00:00');
        $sql_end = date('Y-m-t 23:59:59');
        $laporan_title = 'Laporan Bulanan';
        break;
    case 'tahunan':
        $sql_start = date('Y-01-01 00:00:00');
        $sql_end = date('Y-12-31 23:59:59');
        $laporan_title = 'Laporan Tahunan';
        break;
    case 'custom':
        $start_datetime_val = $_GET['start_datetime'] ?? date('Y-m-d') . 'T00:00';
        $end_datetime_val = $_GET['end_datetime'] ?? date('Y-m-d') . 'T23:59';
        $sql_start = date('Y-m-d H:i:s', strtotime($start_datetime_val));
        $sql_end = date('Y-m-d H:i:s', strtotime($end_datetime_val));
        $laporan_title = 'Laporan Kustom';
        break;
    case 'harian':
    default:
        $sql_start = date('Y-m-d 00:00:00');
        $sql_end = date('Y-m-d 23:59:59');
        $laporan_title = 'Laporan Hari Ini';
        $filter_type = 'harian';
        break;
}

if(empty($start_datetime_val)) $start_datetime_val = $_GET['start_datetime'] ?? date('Y-m-d') . 'T00:00';
if(empty($end_datetime_val)) $end_datetime_val = $_GET['end_datetime'] ?? date('Y-m-d') . 'T23:59';

// Ambil Data Transaksi (List)
$transaksi_list = [];
$stmt_list = $db->prepare("SELECT t.*, k.plat_nomor, k.jenis, u.nama AS nama_petugas FROM transaksi_parkir t JOIN kendaraan k ON t.id_kendaraan = k.id LEFT JOIN users u ON t.id_petugas_keluar = u.id WHERE t.status = 'keluar' AND t.waktu_keluar BETWEEN ? AND ? ORDER BY t.waktu_keluar DESC");
$stmt_list->bind_param("ss", $sql_start, $sql_end);
$stmt_list->execute();
$result_list = $stmt_list->get_result();
while ($row = $result_list->fetch_assoc()) { $transaksi_list[] = $row; }
$stmt_list->close();

// Ambil Total Pendapatan (Realtime Display)
$stmt_total = $db->prepare("SELECT SUM(biaya) AS total FROM transaksi_parkir WHERE status = 'keluar' AND waktu_keluar BETWEEN ? AND ?");
$stmt_total->bind_param("ss", $sql_start, $sql_end);
$stmt_total->execute();
$total_pendapatan = $stmt_total->get_result()->fetch_assoc()['total'] ?? 0;
$stmt_total->close();

// --- AMBIL DAFTAR ARSIP (DATA MENTAH / ENCRYPTED) ---
$arsip_list = [];
$res_arsip = $db->query("SELECT * FROM laporan_keuangan ORDER BY id DESC LIMIT 12");
while ($row = $res_arsip->fetch_assoc()) {
    $arsip_list[] = $row;
}


// --- LOGIKA DOWNLOAD PDF (Realtime) ---
if (isset($_GET['download_pdf'])) {
    $html_pdf = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='utf-8'>
        <title>Laporan Pendapatan</title>
        <style>
            body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 10px; }
            .header { text-align: center; margin-bottom: 20px; }
            .header h1 { margin: 0; font-size: 24px; }
            table { width: 100%; border-collapse: collapse; font-size: 9px; }
            th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
            th { background-color: #f2f2f2; }
            .total-table { width: 40%; float: right; margin-top: 15px; }
            .total-table td { border: none; padding: 4px; font-size: 11px; }
        </style>
    </head>
    <body>
        <div class='header'>
            <h1>Laporan Pendapatan Parkir</h1>
            <p><strong>Periode: " . $laporan_title . "</strong></p>
            <p>(" . date('d/m/Y H:i', strtotime($sql_start)) . " - " . date('d/m/Y H:i', strtotime($sql_end)) . ")</p>
        </div>
        <table>
            <thead>
                <tr>
                    <th>No</th><th>Kode</th><th>Plat</th><th>Jenis</th><th>Masuk</th><th>Keluar</th><th>Durasi</th><th>Biaya</th><th>Petugas</th>
                </tr>
            </thead>
            <tbody>";
    
    if (empty($transaksi_list)) {
        $html_pdf .= "<tr><td colspan='9' style='text-align: center;'>Tidak ada data.</td></tr>";
    } else {
        $no = 1;
        foreach ($transaksi_list as $trx) {
            $durasi = (new DateTime($trx['waktu_keluar']))->diff(new DateTime($trx['waktu_masuk']));
            $html_pdf .= "<tr>
                <td>" . $no++ . "</td>
                <td>" . htmlspecialchars($trx['kode_barcode']) . "</td>
                <td>" . htmlspecialchars($trx['plat_nomor']) . "</td>
                <td>" . ucfirst($trx['jenis']) . "</td>
                <td>" . date('d/m/y H:i', strtotime($trx['waktu_masuk'])) . "</td>
                <td>" . date('d/m/y H:i', strtotime($trx['waktu_keluar'])) . "</td>
                <td>" . $durasi->h . 'j ' . $durasi->i . 'm' . "</td>
                <td style='text-align: right;'>" . number_format($trx['biaya'], 0, ',', '.') . "</td>
                <td>" . htmlspecialchars($trx['nama_petugas']) . "</td>
            </tr>";
        }
    }
    $html_pdf .= "</tbody></table>
        <table class='total-table'>
            <tr>
                <td><strong>Total Pendapatan:</strong></td>
                <td style='text-align: right;'><strong>Rp " . number_format($total_pendapatan, 0, ',', '.') . "</strong></td>
            </tr>
        </table>
    </body></html>";
    
    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', true);
    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html_pdf);
    $dompdf->setPaper('A4', 'landscape');
    $dompdf->render();
    $dompdf->stream("laporan.pdf", ["Attachment" => 1]);
    exit;
}

$db->close();
?>

<?php require_once '../../templates/header_app.php'; ?>
<?php require_once '../../templates/sidebar.php'; ?>

<div class="flex-1 flex flex-col overflow-hidden">
    <?php require_once '../../templates/navbar_app.php'; ?>

    <main class="flex-1 overflow-x-hidden overflow-y-auto bg-white p-6">
        <div class="container mx-auto max-w-7xl">

            <?php if (isset($_SESSION['success_message'])): ?>
                <script>Swal.fire('Berhasil!', '<?php echo $_SESSION['success_message']; ?>', 'success');</script>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>
            <?php if (isset($_SESSION['error_message'])): ?>
                <script>Swal.fire('Gagal!', '<?php echo $_SESSION['error_message']; ?>', 'error');</script>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>

            <div class="mb-6 flex justify-between items-center">
                <div>
                    <h2 class="text-2xl md:text-3xl font-bold text-gray-900 tracking-tight">Laporan Keuangan</h2>
                    <p class="text-gray-500">Pantau pendapatan real-time dan kelola arsip laporan.</p>
                </div>
                <button onclick="document.getElementById('modalGenerate').classList.remove('hidden')" 
                        class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded-xl shadow-lg flex items-center transition transform hover:scale-105">
                    <i class="fas fa-lock mr-2"></i> Generate & Arsip Laporan
                </button>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
                <div class="mb-5 pb-5 border-b border-gray-100">
                    <h3 class="text-base font-semibold text-gray-800 mb-3">Filter Cepat</h3>
                    <div class="flex flex-wrap gap-2">
                        <a href="?filter_type=harian" class="px-4 py-2 rounded-xl text-sm font-medium <?php echo ($filter_type == 'harian') ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700'; ?>">Hari Ini</a>
                        <a href="?filter_type=mingguan" class="px-4 py-2 rounded-xl text-sm font-medium <?php echo ($filter_type == 'mingguan') ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700'; ?>">Minggu Ini</a>
                        <a href="?filter_type=bulanan" class="px-4 py-2 rounded-xl text-sm font-medium <?php echo ($filter_type == 'bulanan') ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700'; ?>">Bulan Ini</a>
                    </div>
                </div>
                <form action="laporan.php" method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                    <input type="hidden" name="filter_type" value="custom">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Dari Tanggal</label>
                        <input type="datetime-local" name="start_datetime" class="w-full border border-gray-300 rounded-xl px-3 py-2" value="<?php echo $start_datetime_val; ?>">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Sampai Tanggal</label>
                        <input type="datetime-local" name="end_datetime" class="w-full border border-gray-300 rounded-xl px-3 py-2" value="<?php echo $end_datetime_val; ?>">
                    </div>
                    <button type="submit" class="bg-blue-600 text-white font-semibold py-2.5 px-4 rounded-xl">Filter Kustom</button>
                </form>
            </div>

            <div class="bg-white rounded-2xl border border-blue-100 shadow-sm p-6 mb-8">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-sm font-medium text-blue-700">Total Pendapatan (Realtime - <?php echo $laporan_title; ?>)</p>
                        <p class="text-4xl font-extrabold text-gray-900">Rp <?php echo number_format($total_pendapatan, 0, ',', '.'); ?></p>
                    </div>
                    <a href="laporan.php?download_pdf=1&filter_type=<?php echo $filter_type; ?>" class="bg-green-600 text-white px-4 py-2 rounded-lg">
                        <i class="fas fa-file-pdf mr-2"></i> Download PDF
                    </a>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 mb-8 overflow-hidden">
                <div class="bg-gray-50 px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                    <div class="flex items-center">
                        <h3 class="text-lg font-bold text-gray-800"><i class="fas fa-archive mr-2"></i> Arsip Laporan Bulanan</h3>
                        <?php if($is_decrypted_mode): ?>
                            <span class="ml-3 text-xs bg-green-100 text-green-800 px-2 py-1 rounded-full border border-green-200 font-bold">
                                <i class="fas fa-lock-open"></i> DECRYPTED MODE
                            </span>
                        <?php else: ?>
                            <span class="ml-3 text-xs bg-red-100 text-red-800 px-2 py-1 rounded-full border border-red-200 font-bold">
                                <i class="fas fa-lock"></i> ENCRYPTED MODE
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <a href="?toggle_mode=1" class="<?php echo $is_decrypted_mode ? 'bg-gray-200 text-gray-700 hover:bg-gray-300' : 'bg-indigo-600 text-white hover:bg-indigo-700'; ?> px-4 py-2 rounded-lg text-sm font-bold transition shadow-sm flex items-center">
                        <?php if($is_decrypted_mode): ?>
                            <i class="fas fa-eye-slash mr-2"></i> Sembunyikan Data
                        <?php else: ?>
                            <i class="fas fa-eye mr-2"></i> Tampilkan Data Asli
                        <?php endif; ?>
                    </a>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="px-6 py-3 text-left font-semibold text-gray-600">Periode</th>
                                <th class="px-6 py-3 text-left font-semibold text-gray-600">
                                    Total Pendapatan 
                                    <?php echo $is_decrypted_mode ? '(Dekripsi/Asli)' : '(Terenkripsi/Acak)'; ?>
                                </th>
                                <th class="px-6 py-3 text-left font-semibold text-gray-600">Tanggal Arsip</th>
                                <th class="px-6 py-3 text-left font-semibold text-gray-600">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php if (empty($arsip_list)): ?>
                                <tr><td colspan="4" class="px-6 py-4 text-center text-gray-500">Belum ada laporan diarsip.</td></tr>
                            <?php else: ?>
                                <?php foreach ($arsip_list as $arsip): ?>
                                    
                                    <?php 
                                        // --- LOGIKA UTAMA TAMPILAN DATA ---
                                        if ($is_decrypted_mode) {
                                            // 1. Jika Mode Dekripsi Aktif -> Lakukan Decrypt & Format Rupiah
                                            $pendapatan_raw = decrypt_data($arsip['total_pendapatan']);
                                            
                                            $tampil_pendapatan = "Rp " . number_format($pendapatan_raw, 0, ',', '.');
                                            $style_class = "font-bold text-green-600 text-base"; // Teks hijau & tebal
                                            $icon_status = "<span class='text-xs bg-green-100 text-green-700 px-2 py-1 rounded'><i class='fas fa-check-circle'></i> Terbaca</span>";
                                        } else {
                                            // 2. Jika Mode Enkripsi (Default) -> Tampilkan Kode Acak
                                            $tampil_pendapatan = htmlspecialchars($arsip['total_pendapatan']);
                                            $style_class = "font-mono text-xs text-red-600 break-all"; // Teks merah & font kode
                                            $icon_status = "<span class='text-xs bg-red-100 text-red-700 px-2 py-1 rounded'><i class='fas fa-lock'></i> Aman</span>";
                                        }
                                    ?>

                                    <tr>
                                        <td class="px-6 py-4 font-medium text-gray-900"><?php echo $arsip['periode']; ?></td>
                                        
                                        <td class="px-6 py-4 <?php echo $style_class; ?>" style="max-width: 300px;">
                                            <?php echo $tampil_pendapatan; ?>
                                        </td>

                                        <td class="px-6 py-4 text-gray-500"><?php echo date('d M Y H:i', strtotime($arsip['tanggal_dibuat'])); ?></td>
                                        <td class="px-6 py-4"><?php echo $icon_status; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            </div>
    </main>
</div>

<div id="modalGenerate" class="fixed inset-0 bg-gray-900 bg-opacity-50 hidden flex items-center justify-center z-50">
    <div class="bg-white rounded-xl shadow-2xl w-96 p-6">
        <h3 class="text-xl font-bold text-gray-800 mb-4">Generate Laporan Bulanan</h3>
        <p class="text-sm text-gray-600 mb-4">Pilih bulan untuk disimpan. Data akan <span class="font-bold text-red-600">DIENKRIPSI</span> di database.</p>
        <form action="laporan.php" method="POST">
            <input type="hidden" name="generate_laporan" value="1">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Pilih Bulan</label>
                <input type="month" name="bulan" required class="w-full border border-gray-300 rounded-lg px-3 py-2">
            </div>
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="document.getElementById('modalGenerate').classList.add('hidden')" class="px-4 py-2 bg-gray-200 rounded-lg">Batal</button>
                <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded-lg">Simpan & Enkripsi</button>
            </div>
        </form>
    </div>
</div>

<?php require_once '../../templates/footer_app.php'; ?>