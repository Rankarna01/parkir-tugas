<?php
//=========================================
// LOGIKA PHP (BAGIAN ATAS)
//=========================================
$page_title = "Riwayat Kendaraan";
require_once '../../core/init.php';

// Keamanan Halaman
if ($_SESSION['role'] != 'pekerja') {
    header('Location: ../../login.php');
    exit;
}

// --- LOGIKA PENCARIAN & PAGINATION ---
$limit = 10; // Jumlah data per halaman
$page = (int)($_GET['page'] ?? 1); // Halaman saat ini
$offset = ($page - 1) * $limit;

// Cek apakah ada pencarian
$search_term = $_GET['search'] ?? '';
$search_param = "%" . $search_term . "%";

// Siapkan query WHERE untuk pencarian
$where_clause = "";
if (!empty($search_term)) {
    $where_clause = "WHERE (k.plat_nomor LIKE ? OR t.kode_barcode LIKE ?)";
}

// --- AMBIL DATA TRANSAKSI (DENGAN PAGINATION & SEARCH) ---
$transaksi_list = [];
$query_data = "SELECT 
                    t.id, t.kode_barcode, t.waktu_masuk, t.waktu_keluar, t.status, t.biaya,
                    k.plat_nomor, k.jenis
               FROM transaksi_parkir t
               JOIN kendaraan k ON t.id_kendaraan = k.id
               $where_clause
               ORDER BY t.waktu_masuk DESC
               LIMIT ? OFFSET ?";

$stmt_data = $db->prepare($query_data);
if (!empty($search_term)) {
    $stmt_data->bind_param("ssii", $search_param, $search_param, $limit, $offset);
} else {
    $stmt_data->bind_param("ii", $limit, $offset);
}
$stmt_data->execute();
$result_data = $stmt_data->get_result();
while ($row = $result_data->fetch_assoc()) {
    $transaksi_list[] = $row;
}
$stmt_data->close();

// --- HITUNG TOTAL DATA (UNTUK PAGINATION) ---
$query_total = "SELECT COUNT(t.id) AS total 
                FROM transaksi_parkir t
                JOIN kendaraan k ON t.id_kendaraan = k.id
                $where_clause";

$stmt_total = $db->prepare($query_total);
if (!empty($search_term)) {
    $stmt_total->bind_param("ss", $search_param, $search_param);
}
$stmt_total->execute();
$total_data = $stmt_total->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_data / $limit);
$stmt_total->close();

$db->close();

// --- Fungsi Helper untuk format durasi ---
function formatDurasi($waktu_masuk_dt, $waktu_referensi_dt) {
    $durasi = $waktu_referensi_dt->diff($waktu_masuk_dt);
    $format = '';
    if ($durasi->d > 0) $format .= $durasi->d . ' hari, ';
    if ($durasi->h > 0) $format .= $durasi->h . ' jam, ';
    $format .= $durasi->i . ' mnt';
    return $format;
}

//=========================================
// TAMPILAN HTML (BAGIAN BAWAH)
//=========================================
?>

<?php require_once '../../templates/header_app.php'; // Header ?>

<?php require_once '../../templates/sidebar.php'; // Sidebar ?>

<div class="flex-1 flex flex-col overflow-hidden">
    
    <?php require_once '../../templates/navbar_app.php'; // Navbar ?>

    <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6">
        
        <div class="container mx-auto">
            <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
                <div>
                    <h2 class="text-2xl font-semibold text-gray-800">Riwayat Kendaraan</h2>
                    <p class="text-gray-600">Menampilkan semua kendaraan yang masuk dan keluar.</p>
                </div>
                <form action="riwayat_kendaraan.php" method="GET" class="w-full md:w-1/3">
                    <div class="relative">
                        <input type="text" name="search"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                               placeholder="Cari Plat Nomor / Kode Tiket..."
                               value="<?php echo htmlspecialchars($search_term); ?>">
                        <button type="submit" class="absolute right-0 top-0 h-full px-4 text-gray-500 hover:text-blue-600">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>
            </div>

            <div class="bg-white p-6 rounded-lg shadow-md">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Plat Nomor</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kode Tiket</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Waktu Masuk</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Waktu Keluar</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Durasi</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Biaya</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($transaksi_list)): ?>
                                <tr>
                                    <td colspan="8" class="px-6 py-4 text-center text-gray-500">
                                        <?php echo !empty($search_term) ? 'Data tidak ditemukan.' : 'Belum ada data transaksi.'; ?>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($transaksi_list as $trx): ?>
                                    <?php
                                    // --- Logika Perhitungan Durasi & Biaya ---
                                    $waktu_masuk_dt = new DateTime($trx['waktu_masuk']);
                                    $durasi_format = '';
                                    $biaya_format = 'Rp -';
                                    
                                    if ($trx['status'] == 'masuk') {
                                        // Jika masih parkir, hitung durasi sampai SEKARANG
                                        $waktu_referensi_dt = new DateTime(); // Waktu sekarang
                                        $durasi_format = formatDurasi($waktu_masuk_dt, $waktu_referensi_dt);
                                    } else {
                                        // Jika sudah keluar, hitung durasi berdasarkan data
                                        $waktu_referensi_dt = new DateTime($trx['waktu_keluar']);
                                        $durasi_format = formatDurasi($waktu_masuk_dt, $waktu_referensi_dt);
                                        $biaya_format = 'Rp ' . number_format($trx['biaya'], 0, ',', '.');
                                    }
                                    ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($trx['plat_nomor']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700"><?php echo htmlspecialchars($trx['kode_barcode']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php if ($trx['status'] == 'masuk'): ?>
                                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                    Masuk
                                                </span>
                                            <?php else: ?>
                                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                    Keluar
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700"><?php echo date('d/m/y H:i', strtotime($trx['waktu_masuk'])); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                            <?php echo ($trx['waktu_keluar']) ? date('d/m/y H:i', strtotime($trx['waktu_keluar'])) : '-'; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700"><?php echo $durasi_format; ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900"><?php echo $biaya_format; ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <?php if ($trx['status'] == 'masuk'): ?>
                                                <a href="../../cetak_tiket.php?id=<?php echo $trx['id']; ?>" target="_blank" class="text-blue-600 hover:text-blue-900" title="Cetak Tiket Masuk">
                                                    <i class="fas fa-ticket-alt"></i> Cetak Tiket
                                                </a>
                                            <?php else: ?>
                                                <a href="../../cetak_struk.php?id=<?php echo $trx['id']; ?>" target="_blank" class="text-indigo-600 hover:text-indigo-900" title="Cetak Struk Pembayaran">
                                                    <i class="fas fa-receipt"></i> Cetak Struk
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($total_pages > 1): ?>
                    <div class="mt-6 flex justify-between items-center">
                        <span class="text-sm text-gray-700">
                            Halaman <?php echo $page; ?> dari <?php echo $total_pages; ?>
                        </span>
                        <div class="flex space-x-2">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search_term); ?>" class="px-4 py-2 text-sm text-white bg-blue-500 rounded-lg hover:bg-blue-600">
                                    &laquo; Sebelumnya
                                </a>
                            <?php endif; ?>
                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search_term); ?>" class="px-4 py-2 text-sm text-white bg-blue-500 rounded-lg hover:bg-blue-600">
                                    Selanjutnya &raquo;
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
            </div>
        </div>
    </main>
</div>

<?php require_once '../../templates/footer_app.php'; // Footer ?>