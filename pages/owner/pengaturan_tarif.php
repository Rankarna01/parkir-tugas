<?php
//=========================================
// LOGIKA PHP (BAGIAN ATAS)
//=========================================
$page_title = "Pengaturan Tarif";
require_once '../../core/init.php';

// Keamanan Halaman
if ($_SESSION['role'] != 'owner') {
    header('Location: ../../login.php');
    exit;
}

// === PROSES UPDATE DATA (Method POST) ===
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_tarif'])) {
    $id = $_POST['id_tarif'];
    $tarif_per_jam = $_POST['tarif_per_jam'];

    // Validasi sederhana
    if (!empty($id) && is_numeric($tarif_per_jam)) {
        $stmt = $db->prepare("UPDATE tarif_parkir SET tarif_per_jam = ? WHERE id = ?");
        $stmt->bind_param("di", $tarif_per_jam, $id);
        
        if ($stmt->execute()) {
            // Set session flash message untuk notifikasi SweetAlert
            $_SESSION['success_message'] = "Tarif berhasil diperbarui!";
        } else {
            $_SESSION['error_message'] = "Gagal memperbarui tarif.";
        }
        $stmt->close();
    } else {
        $_SESSION['error_message'] = "Data tidak valid.";
    }

    // Redirect ke halaman ini lagi untuk refresh data & menghindari resubmit form
    header('Location: pengaturan_tarif.php');
    exit;
}

// === PROSES AMBIL DATA (Method GET) ===
$tarifs = [];
$result = $db->query("SELECT * FROM tarif_parkir ORDER BY jenis_kendaraan");
while ($row = $result->fetch_assoc()) {
    $tarifs[] = $row;
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
            <div class="mb-6">
                <h2 class="text-2xl font-semibold text-gray-800">Manajemen Tarif Parkir</h2>
                <p class="text-gray-600">Atur biaya parkir per jam untuk setiap jenis kendaraan.</p>
            </div>

            <div class="bg-white p-6 rounded-lg shadow-md">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Jenis Kendaraan
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Tarif per Jam
                                </th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Aksi
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($tarifs as $tarif): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900 capitalize">
                                        <?php echo htmlspecialchars($tarif['jenis_kendaraan']); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        Rp <?php echo number_format($tarif['tarif_per_jam'], 0, ',', '.'); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <button class="btn-edit text-blue-600 hover:text-blue-900"
                                            data-id="<?php echo $tarif['id']; ?>"
                                            data-jenis="<?php echo htmlspecialchars($tarif['jenis_kendaraan']); ?>"
                                            data-tarif="<?php echo $tarif['tarif_per_jam']; ?>">
                                        <i class="fas fa-edit mr-1"></i> Ubah
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>
</div>

<div id="editModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full flex items-center justify-center hidden z-50">
    <div class="bg-white p-8 rounded-lg shadow-xl w-full max-w-md">
        
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-2xl font-semibold text-gray-800">Ubah Tarif</h3>
            <button id="btnBatal" class="text-gray-400 hover:text-gray-600">&times;</button>
        </div>

        <form action="pengaturan_tarif.php" method="POST">
            <input type="hidden" id="modal_id_tarif" name="id_tarif">
            <input type="hidden" name="update_tarif" value="1">

            <div class="mb-4">
                <label for="modal_jenis_kendaraan" class="block text-sm font-medium text-gray-700 mb-2">Jenis Kendaraan</label>
                <input type="text" id="modal_jenis_kendaraan" name="jenis_kendaraan"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-100 focus:outline-none"
                       readonly>
            </div>
            
            <div class="mb-6">
                <label for="modal_tarif_per_jam" class="block text-sm font-medium text-gray-700 mb-2">Tarif per Jam (Rp)</label>
                <input type="number" id="modal_tarif_per_jam" name="tarif_per_jam"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                       placeholder="Contoh: 5000" required>
            </div>
            
            <div class="flex justify-end space-x-4">
                <button type="button" id="btnBatalModal" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-lg hover:bg-gray-400 transition duration-150">
                    Batal
                </button>
                <button type="submit"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition duration-150">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once '../../templates/footer_app.php'; // Footer ?>

<script>
$(document).ready(function() {
    
    // 1. Tampilkan Modal saat tombol "Ubah" diklik
    $('.btn-edit').on('click', function() {
        // Ambil data dari tombol
        var id = $(this).data('id');
        var jenis = $(this).data('jenis');
        var tarif = $(this).data('tarif');

        // Isi form di dalam modal
        $('#modal_id_tarif').val(id);
        $('#modal_jenis_kendaraan').val(jenis);
        $('#modal_tarif_per_jam').val(tarif);

        // Tampilkan modal
        $('#editModal').removeClass('hidden').addClass('flex');
    });

    // 2. Sembunyikan Modal saat tombol "Batal" (di dalam modal) diklik
    $('#btnBatal, #btnBatalModal').on('click', function() {
        $('#editModal').addClass('hidden').removeClass('flex');
    });

    // 3. Tampilkan Notifikasi SweetAlert (jika ada session flash message)
    <?php if (isset($_SESSION['success_message'])): ?>
        Swal.fire({
            title: 'Berhasil!',
            text: '<?php echo $_SESSION['success_message']; ?>',
            icon: 'success',
            confirmButtonText: 'OK'
        });
        <?php unset($_SESSION['success_message']); // Hapus session setelah ditampilkan ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error_message'])): ?>
        Swal.fire({
            title: 'Gagal!',
            text: '<?php echo $_SESSION['error_message']; ?>',
            icon: 'error',
            confirmButtonText: 'OK'
        });
        <?php unset($_SESSION['error_message']); // Hapus session setelah ditampilkan ?>
    <?php endif; ?>

});
</script>