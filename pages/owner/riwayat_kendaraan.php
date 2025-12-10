<?php
//=========================================
// LOGIKA PHP (BAGIAN ATAS)
//=========================================
$page_title = "Riwayat Kendaraan";
require_once '../../core/init.php';

// Keamanan Halaman (INI YANG DIUBAH)
if ($_SESSION['role'] != 'owner') {
    header('Location: ../../login.php');
    exit;
}
// ------------------------------------

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

    <!-- Background putih bersih + ruang lega -->
    <main class="flex-1 overflow-x-hidden overflow-y-auto bg-white p-6">
        <div class="container mx-auto max-w-7xl">
            
            <!-- Header + Search -->
            <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
                <div>
                    <h2 class="text-2xl md:text-3xl font-bold text-gray-900 tracking-tight">Riwayat Kendaraan</h2>
                    <p class="text-gray-500">Menampilkan semua kendaraan yang masuk dan keluar.</p>
                </div>

                <form action="riwayat_kendaraan.php" method="GET" class="w-full md:w-1/3">
                    <div class="relative group">
                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-blue-600"></i>
                        <input
                            type="text"
                            name="search"
                            class="w-full pl-10 pr-12 py-2.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition text-gray-800 placeholder-gray-400"
                            placeholder="Cari Plat Nomor / Kode Tiket..."
                            value="<?php echo htmlspecialchars($search_term); ?>"
                        />
                        <?php if (!empty($search_term)): ?>
                        <a href="riwayat_kendaraan.php"
                           class="absolute right-2 top-1/2 -translate-y-1/2 text-xs px-2 py-1 rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200">
                           Reset
                        </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Card Tabel -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
                <!-- Title strip -->
                <div class="p-6 pb-3 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Daftar Transaksi</h3>
                        <p class="text-sm text-gray-500">Riwayat kendaraan masuk & keluar sesuai pencarian.</p>
                    </div>
                    <div class="hidden md:flex items-center gap-2 text-xs">
                        <span class="px-2 py-1 rounded-full bg-blue-50 text-blue-700 border border-blue-200">
                            <?php echo !empty($transaksi_list) ? count($transaksi_list) : 0; ?> entri
                        </span>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 sticky top-0 z-10">
                            <tr class="text-left">
                                <th class="px-6 py-3 font-semibold text-gray-600 uppercase">Plat Nomor</th>
                                <th class="px-6 py-3 font-semibold text-gray-600 uppercase">Kode Tiket</th>
                                <th class="px-6 py-3 font-semibold text-gray-600 uppercase">Status</th>
                                <th class="px-6 py-3 font-semibold text-gray-600 uppercase">Waktu Masuk</th>
                                <th class="px-6 py-3 font-semibold text-gray-600 uppercase">Waktu Keluar</th>
                                <th class="px-6 py-3 font-semibold text-gray-600 uppercase">Durasi</th>
                                <th class="px-6 py-3 font-semibold text-gray-600 uppercase">Biaya</th>
                                <th class="px-6 py-3 font-semibold text-gray-600 uppercase text-right">Aksi</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-100">
                            <?php if (empty($transaksi_list)): ?>
                                <tr>
                                    <td colspan="8" class="px-6 py-8 text-center text-gray-500">
                                        <?php echo !empty($search_term) ? 'Data tidak ditemukan.' : 'Belum ada data transaksi.'; ?>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($transaksi_list as $trx): ?>
                                    <?php
                                    // --- Logika Perhitungan Durasi & Biaya (tetap) ---
                                    $waktu_masuk_dt = new DateTime($trx['waktu_masuk']);
                                    $durasi_format = '';
                                    $biaya_format = 'Rp -';
                                    
                                    if ($trx['status'] == 'masuk') {
                                        $waktu_referensi_dt = new DateTime(); // sekarang
                                        $durasi_format = formatDurasi($waktu_masuk_dt, $waktu_referensi_dt);
                                    } else {
                                        $waktu_referensi_dt = new DateTime($trx['waktu_keluar']);
                                        $durasi_format = formatDurasi($waktu_masuk_dt, $waktu_referensi_dt);
                                        $biaya_format = 'Rp ' . number_format($trx['biaya'], 0, ',', '.');
                                    }
                                    ?>
                                    <tr class="hover:bg-gray-50/60 transition">
                                        <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900"><?php echo htmlspecialchars($trx['plat_nomor']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-gray-700"><?php echo htmlspecialchars($trx['kode_barcode']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php if ($trx['status'] == 'masuk'): ?>
                                                <span class="px-2.5 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-700 border border-green-200">
                                                    <i class="fas fa-door-open mr-1"></i> Masuk
                                                </span>
                                            <?php else: ?>
                                                <span class="px-2.5 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-700 border border-red-200">
                                                    <i class="fas fa-door-closed mr-1"></i> Keluar
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-gray-700"><?php echo date('d/m/y H:i', strtotime($trx['waktu_masuk'])); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-gray-700">
                                            <?php echo ($trx['waktu_keluar']) ? date('d/m/y H:i', strtotime($trx['waktu_keluar'])) : '-'; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-gray-700"><?php echo $durasi_format; ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap font-semibold text-gray-900"><?php echo $biaya_format; ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right">
                                            <?php if ($trx['status'] == 'masuk'): ?>
                                                <a href="../../cetak_tiket.php?id=<?php echo $trx['id']; ?>" target="_blank"
                                                   class="inline-flex items-center gap-2 text-white bg-blue-600 hover:bg-blue-700 px-3 py-2 rounded-lg text-xs font-medium shadow-sm"
                                                   title="Cetak Tiket Masuk">
                                                    <i class="fas fa-ticket-alt"></i> Cetak Tiket
                                                </a>
                                            <?php else: ?>
                                                <a href="../../cetak_struk.php?id=<?php echo $trx['id']; ?>" target="_blank"
                                                   class="inline-flex items-center gap-2 text-white bg-indigo-600 hover:bg-indigo-700 px-3 py-2 rounded-lg text-xs font-medium shadow-sm"
                                                   title="Cetak Struk Pembayaran">
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

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="px-6 py-4 border-t border-gray-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <span class="text-sm text-gray-600">
                            Halaman <span class="font-semibold text-gray-900"><?php echo $page; ?></span> dari <?php echo $total_pages; ?>
                        </span>
                        <div class="flex gap-2">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search_term); ?>"
                                   class="px-4 py-2 text-sm text-blue-700 bg-blue-50 hover:bg-blue-100 border border-blue-200 rounded-xl transition">
                                    &laquo; Sebelumnya
                                </a>
                            <?php else: ?>
                                <span class="px-4 py-2 text-sm text-gray-400 bg-gray-50 border border-gray-200 rounded-xl cursor-not-allowed">
                                    &laquo; Sebelumnya
                                </span>
                            <?php endif; ?>

                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search_term); ?>"
                                   class="px-4 py-2 text-sm text-blue-700 bg-blue-50 hover:bg-blue-100 border border-blue-200 rounded-xl transition">
                                    Selanjutnya &raquo;
                                </a>
                            <?php else: ?>
                                <span class="px-4 py-2 text-sm text-gray-400 bg-gray-50 border border-gray-200 rounded-xl cursor-not-allowed">
                                    Selanjutnya &raquo;
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<?php require_once '../../templates/footer_app.php'; // Footer ?>
