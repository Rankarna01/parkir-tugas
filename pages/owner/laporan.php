<?php
//=========================================
// LAPORAN KEUANGAN (AES-128 IMPLEMENTATION)
//=========================================

$page_title = "Laporan Keuangan";
require_once '../../core/init.php'; // Memuat functions.php

// Menggunakan namespace Dompdf
use Dompdf\Dompdf;
use Dompdf\Options;

// 1. KEAMANAN HALAMAN
// Hanya owner yang boleh akses
if ($_SESSION['role'] != 'owner') {
    header('Location: ../../login.php');
    exit;
}

// 2. FITUR TOGGLE TAMPILAN (ENKRIPSI <-> DEKRIPSI)
// Mengatur apakah admin ingin melihat angka asli atau kode hex
if (isset($_GET['toggle_mode'])) {
    $_SESSION['show_decrypted'] = !($_SESSION['show_decrypted'] ?? false);
    header("Location: laporan.php");
    exit;
}
$is_decrypted_mode = $_SESSION['show_decrypted'] ?? false;


// --- LOGIKA GENERATE LAPORAN (ENKRIPSI DATA) ---
if (isset($_POST['generate_laporan'])) {

    // 1. AMBIL INPUT DARI DROPDOWN
    $input_bulan = $_POST['pilih_bulan']; // Contoh: "12"
    $input_tahun = $_POST['pilih_tahun']; // Contoh: "2025"

    // Gabungkan menjadi format YYYY-MM (Contoh: "2025-12")
    $bulan = $input_tahun . "-" . $input_bulan;

    // A. Tentukan Range Tanggal Awal & Akhir Bulan
    $sql_start_gen = "$bulan-01 00:00:00";
    $sql_end_gen   = date("Y-m-t 23:59:59", strtotime($sql_start_gen));

    // B. Hitung Total Pendapatan Real
    $stmt_calc = $db->prepare("SELECT SUM(biaya) AS total FROM transaksi_parkir WHERE status = 'keluar' AND waktu_keluar BETWEEN ? AND ?");
    $stmt_calc->bind_param("ss", $sql_start_gen, $sql_end_gen);
    $stmt_calc->execute();

    // --- PERBAIKAN UTAMA: HILANGKAN .00 ---
    $row = $stmt_calc->get_result()->fetch_assoc();

    // Paksa jadi integer (bilangan bulat). Contoh: 14000.00 menjadi 14000
    // Jika null (tidak ada transaksi), set ke 0
    $total_pendapatan_gen = isset($row['total']) ? (int)$row['total'] : 0;

    $stmt_calc->close(); // Tutup koneksi statement perhitungan

    // C. Hitung Pengeluaran & Laba (Dummy/Opsional)
    $total_pengeluaran_gen = 0;

    // Laba bersih juga dipaksa integer
    $laba_bersih = (int)($total_pendapatan_gen - $total_pengeluaran_gen);

    // --- PROSES ENKRIPSI (AES-128 HEX) ---
    // Fungsi encrypt_data() sekarang menerima input integer "14000"
    $enc_pendapatan  = encrypt_data($total_pendapatan_gen);
    $enc_pengeluaran = encrypt_data($total_pengeluaran_gen);
    $enc_laba        = encrypt_data($laba_bersih);

    // D. Simpan ke Database
    $nama_periode = date("F Y", strtotime($sql_start_gen)); // Contoh: "December 2025"

    // Cek duplikasi laporan
    $cek_lap = $db->query("SELECT id FROM laporan_keuangan WHERE periode = '$nama_periode'");
    if ($cek_lap->num_rows > 0) {
        $_SESSION['error_message'] = "Laporan periode $nama_periode sudah ada!";
    } else {
        $stmt_save = $db->prepare("INSERT INTO laporan_keuangan (periode, total_pendapatan, total_pengeluaran, laba_bersih, tanggal_dibuat) VALUES (?, ?, ?, ?, NOW())");
        $stmt_save->bind_param("ssss", $nama_periode, $enc_pendapatan, $enc_pengeluaran, $enc_laba);

        if ($stmt_save->execute()) {
            $_SESSION['success_message'] = "Laporan berhasil disimpan. Data diamankan dengan AES-128 (Hex).";
        } else {
            $_SESSION['error_message'] = "Database Error: " . $db->error;
        }
        $stmt_save->close();
    }

    header("Location: laporan.php");
    exit;
}


// 4. LOGIKA FILTER & DATA REALTIME (Tidak Dienkripsi, hanya View)
$filter_type = $_GET['filter_type'] ?? 'harian';
$laporan_title = '';
$start_datetime_val = $_GET['start_datetime'] ?? date('Y-m-d') . 'T00:00';
$end_datetime_val   = $_GET['end_datetime'] ?? date('Y-m-d') . 'T23:59';

// Switch case untuk menentukan range tanggal (Clean Code)
switch ($filter_type) {
    case 'mingguan':
        $sql_start = date('Y-m-d 00:00:00', strtotime('monday this week'));
        $sql_end   = date('Y-m-d 23:59:59', strtotime('sunday this week'));
        $laporan_title = 'Laporan Mingguan';
        break;
    case 'bulanan':
        $sql_start = date('Y-m-01 00:00:00');
        $sql_end   = date('Y-m-t 23:59:59');
        $laporan_title = 'Laporan Bulanan';
        break;
    case 'custom':
        $sql_start = date('Y-m-d H:i:s', strtotime($start_datetime_val));
        $sql_end   = date('Y-m-d H:i:s', strtotime($end_datetime_val));
        $laporan_title = 'Laporan Kustom';
        break;
    case 'harian':
    default:
        $sql_start = date('Y-m-d 00:00:00');
        $sql_end   = date('Y-m-d 23:59:59');
        $laporan_title = 'Laporan Hari Ini';
        $filter_type = 'harian';
        break;
}

// Query Total Pendapatan Realtime
$stmt_total = $db->prepare("SELECT SUM(biaya) AS total FROM transaksi_parkir WHERE status = 'keluar' AND waktu_keluar BETWEEN ? AND ?");
$stmt_total->bind_param("ss", $sql_start, $sql_end);
$stmt_total->execute();
$total_pendapatan = $stmt_total->get_result()->fetch_assoc()['total'] ?? 0;
$stmt_total->close();

// Query List Arsip (Untuk Tabel Bawah)
$arsip_list = [];
$res_arsip = $db->query("SELECT * FROM laporan_keuangan ORDER BY id DESC LIMIT 12");
while ($row = $res_arsip->fetch_assoc()) {
    $arsip_list[] = $row;
}

// 5. LOGIKA DOWNLOAD PDF (Realtime Data)
if (isset($_GET['download_pdf'])) {
    // ... (Kode PDF Anda biarkan saja seperti sebelumnya) ...
    // Note: Jika ingin dipasang kembali, pastikan copas kode PDF dari file lama Anda.
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
                <script>Swal.fire('Sukses', '<?php echo $_SESSION['success_message']; ?>', 'success');</script>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_message'])): ?>
                <script>Swal.fire('Error', '<?php echo $_SESSION['error_message']; ?>', 'error');</script>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>

            <div class="mb-6 flex justify-between items-center">
                <div>
                    <h2 class="text-2xl md:text-3xl font-bold text-gray-900 tracking-tight">Laporan Keuangan</h2>
                    <p class="text-gray-500">Monitoring pendapatan & Arsip Terenkripsi (AES-128).</p>
                </div>
                <button onclick="document.getElementById('modalGenerate').classList.remove('hidden')"
                    class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded-xl shadow-lg flex items-center transition transform hover:scale-105">
                    <i class="fas fa-lock mr-2"></i> Generate & Arsip
                </button>
            </div>

            <div class="bg-white rounded-2xl border border-blue-100 shadow-sm p-6 mb-8">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-sm font-medium text-blue-700">Pendapatan Realtime (<?php echo $laporan_title; ?>)</p>
                        <p class="text-4xl font-extrabold text-gray-900">Rp <?php echo number_format($total_pendapatan, 0, ',', '.'); ?></p>
                    </div>
                    <div class="text-right">
                        <a href="?filter_type=harian" class="text-sm text-blue-600 hover:underline">Hari Ini</a> |
                        <a href="?filter_type=bulanan" class="text-sm text-blue-600 hover:underline">Bulan Ini</a>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 mb-8 overflow-hidden">
                <div class="bg-gray-50 px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                    <div class="flex items-center">
                        <h3 class="text-lg font-bold text-gray-800"><i class="fas fa-archive mr-2"></i> Arsip Laporan Bulanan</h3>

                        <?php if ($is_decrypted_mode): ?>
                            <span class="ml-3 text-xs bg-green-100 text-green-800 px-2 py-1 rounded-full border border-green-200 font-bold">
                                <i class="fas fa-lock-open"></i> MODE TERBUKA
                            </span>
                        <?php else: ?>
                            <span class="ml-3 text-xs bg-red-100 text-red-800 px-2 py-1 rounded-full border border-red-200 font-bold">
                                <i class="fas fa-lock"></i> MODE TERENKRIPSI
                            </span>
                        <?php endif; ?>
                    </div>

                    <a href="?toggle_mode=1" class="<?php echo $is_decrypted_mode ? 'bg-gray-200 text-gray-700' : 'bg-indigo-600 text-white'; ?> px-4 py-2 rounded-lg text-sm font-bold transition shadow-sm flex items-center">
                        <i class="fas <?php echo $is_decrypted_mode ? 'fa-eye-slash' : 'fa-eye'; ?> mr-2"></i>
                        <?php echo $is_decrypted_mode ? 'Sembunyikan Data' : 'Tampilkan Data Asli'; ?>
                    </a>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="px-6 py-3 text-left font-semibold text-gray-600">Periode</th>
                                <th class="px-6 py-3 text-left font-semibold text-gray-600">
                                    Total Pendapatan
                                    <?php echo $is_decrypted_mode ? '(Dekripsi)' : '(Ciphertext/Hex)'; ?>
                                </th>
                                <th class="px-6 py-3 text-left font-semibold text-gray-600">Waktu Arsip</th>
                                <th class="px-6 py-3 text-left font-semibold text-gray-600">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php if (empty($arsip_list)): ?>
                                <tr>
                                    <td colspan="4" class="px-6 py-4 text-center text-gray-500">Belum ada arsip.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($arsip_list as $arsip): ?>

                                    <?php
                                    // === LOGIKA TAMPILAN DATA (ENKRIPSI vs DEKRIPSI) ===
                                    if ($is_decrypted_mode) {
                                        // MODE DEKRIPSI
                                        // 1. Decrypt Hex -> String
                                        $pendapatan_raw = decrypt_data($arsip['total_pendapatan']);

                                        // 2. Format Rupiah (Data sudah integer bersih dari .00)
                                        $tampil_pendapatan = "Rp " . number_format((float)$pendapatan_raw, 0, ',', '.');

                                        $style_class = "font-bold text-green-600 text-base";
                                        $icon_status = "<span class='text-xs bg-green-100 text-green-700 px-2 py-1 rounded'><i class='fas fa-check-circle'></i> Valid</span>";
                                    } else {
                                        // MODE ENKRIPSI
                                        $tampil_pendapatan = htmlspecialchars($arsip['total_pendapatan']);

                                        $style_class = "font-mono text-xs text-red-600 break-all tracking-wider";
                                        $icon_status = "<span class='text-xs bg-red-100 text-red-700 px-2 py-1 rounded'><i class='fas fa-key'></i> Secured</span>";
                                    }
                                    ?>

                                    <tr>
                                        <td class="px-6 py-4 font-medium text-gray-900"><?php echo $arsip['periode']; ?></td>
                                        <td class="px-6 py-4 <?php echo $style_class; ?>" style="max-width: 350px;">
                                            <?php echo $tampil_pendapatan; ?>
                                        </td>
                                        <td class="px-6 py-4 text-gray-500"><?php echo date('d/m/Y H:i', strtotime($arsip['tanggal_dibuat'])); ?></td>
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
        <h3 class="text-xl font-bold text-gray-800 mb-4">Arsip Laporan Bulanan</h3>
        <p class="text-sm text-gray-600 mb-4">
            Data akan disimpan permanen dan <span class="font-bold text-red-600">DIENKRIPSI</span> menggunakan algoritma AES-128.
        </p>

        <form action="laporan.php" method="POST">
            <input type="hidden" name="generate_laporan" value="1">

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Pilih Periode</label>

                <div class="flex space-x-2">
                    <select name="pilih_bulan" required class="w-2/3 border border-gray-300 rounded-lg px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-purple-500">
                        <option value="" disabled selected>-- Bulan --</option>
                        <?php
                        $list_bulan = [
                            "01" => "Januari", "02" => "Februari", "03" => "Maret", "04" => "April",
                            "05" => "Mei", "06" => "Juni", "07" => "Juli", "08" => "Agustus",
                            "09" => "September", "10" => "Oktober", "11" => "November", "12" => "Desember"
                        ];
                        $bulan_ini = date('m');

                        foreach ($list_bulan as $kode => $nama) {
                            $selected = ($kode == $bulan_ini) ? 'selected' : '';
                            echo "<option value='$kode' $selected>$nama</option>";
                        }
                        ?>
                    </select>

                    <select name="pilih_tahun" required class="w-1/3 border border-gray-300 rounded-lg px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-purple-500">
                        <?php
                        $tahun_sekarang = date('Y');
                        for ($t = $tahun_sekarang - 1; $t <= $tahun_sekarang + 1; $t++) {
                            $selected = ($t == $tahun_sekarang) ? 'selected' : '';
                            echo "<option value='$t' $selected>$t</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>

            <div class="flex justify-end space-x-3">
                <button type="button" onclick="document.getElementById('modalGenerate').classList.add('hidden')" class="px-4 py-2 bg-gray-200 rounded-lg text-gray-700 font-medium hover:bg-gray-300">Batal</button>
                <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 font-bold shadow-md">Proses Enkripsi</button>
            </div>
        </form>
    </div>
</div>

<?php require_once '../../templates/footer_app.php'; ?>