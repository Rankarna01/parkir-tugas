<?php
//=========================================
// LOGIKA PHP (BAGIAN ATAS)
//=========================================
$page_title = "Kendaraan Masuk";
require_once '../../core/init.php';

// Keamanan Halaman
if ($_SESSION['role'] != 'pekerja') {
    header('Location: ../../login.php');
    exit;
}

// Ambil data jenis kendaraan & tarif untuk form
$tarif_options = [];
$result = $db->query("SELECT jenis_kendaraan, tarif_per_jam FROM tarif_parkir");
while ($row = $result->fetch_assoc()) {
    $tarif_options[] = $row;
}
$db->close();

//=========================================
// TAMPILAN HTML (BAGIAN BAWAH)
//=========================================
?>

<?php require_once '../../templates/header_app.php'; // Header ?>

<?php require_once '../../templates/sidebar.php'; // Sidebar ?>

<div class="flex-1 flex flex-col overflow-hidden">
    
    <?php require_once '../../templates/navbar_app.php'; // Navbar ?>

    <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6">
        
        <div class="container mx-auto max-w-lg">
            
            <div class="bg-white p-8 rounded-lg shadow-md">
                <h2 class="text-2xl font-semibold text-gray-800 mb-6">Input Kendaraan Masuk</h2>
                
                <form id="formKendaraanMasuk">
                    <input type="hidden" name="id_petugas_masuk" value="<?php echo $_SESSION['user_id']; ?>">
                    <input type="hidden" name="action" value="kendaraan_masuk">

                    <div class="mb-4">
                        <label for="plat_nomor" class="block text-sm font-medium text-gray-700 mb-2">Plat Nomor</label>
                        <input type="text" id="plat_nomor" name="plat_nomor"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 uppercase"
                               placeholder="Contoh: BK 1234 ABC" required>
                    </div>
                    
                    <div class="mb-6">
                        <label for="jenis_kendaraan" class="block text-sm font-medium text-gray-700 mb-2">Jenis Kendaraan</label>
                        <select id="jenis_kendaraan" name="jenis_kendaraan"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                required>
                            <option value="">Pilih Jenis Kendaraan</option>
                            <?php foreach ($tarif_options as $tarif): ?>
                                <option value="<?php echo $tarif['jenis_kendaraan']; ?>">
                                    <?php echo ucfirst($tarif['jenis_kendaraan']); ?> (Rp <?php echo number_format($tarif['tarif_per_jam']); ?>/jam)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Foto Kendaraan (Opsional)</label>
                        <div class="w-full h-48 bg-gray-200 rounded-lg flex items-center justify-center border-2 border-dashed border-gray-300">
                            <i class="fas fa-camera text-gray-400 fa-3x"></i>
                            </div>
                    </div>
                    
                    <div>
                        <button type="submit" id="btnSubmit"
                                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg focus:outline-none focus:shadow-outline transition duration-150 ease-in-out flex items-center justify-center">
                            <i class="fas fa-save mr-2"></i>
                            <span id="btnText">Simpan & Cetak Tiket</span>
                            <i id="btnSpinner" class="fas fa-spinner fa-spin ml-2 hidden"></i>
                        </button>
                    </div>
                </form>
            </div>
            
        </div>
    </main>
</div>

<?php require_once '../../templates/footer_app.php'; // Footer ?>

<script>
$(document).ready(function() {
    
    $('#formKendaraanMasuk').on('submit', function(e) {
        e.preventDefault(); // Mencegah form submit biasa (agar tidak reload)

        // Ubah tampilan tombol
        $('#btnText').text('Menyimpan...');
        $('#btnSubmit').prop('disabled', true);
        $('#btnSpinner').removeClass('hidden');

        // Ambil data form
        var formData = $(this).serialize();

        // Kirim data via AJAX
        $.ajax({
            type: 'POST',
            url: '../../api/ajax_handler.php', // Path ke file handler AJAX kita
            data: formData,
            dataType: 'json', // Harapkan balasan JSON
            
            success: function(response) {
                // Jika server merespon sukses
                if (response.status == 'success') {
                    
                    var transaksi_id = response.data.transaksi_id; // Ambil ID baru dari JSON
                    
                    Swal.fire({
                        title: 'Berhasil!',
                        html: 'Kendaraan berhasil dicatat.<br>' +
                              '<strong>Plat:</strong> ' + response.data.plat_nomor + '<br>' +
                              '<strong>Kode Tiket:</strong> ' + response.data.kode_barcode,
                        icon: 'success',
                        confirmButtonText: 'OK & Cetak Tiket'
                    }).then((result) => {
                        // Jika tombol "OK" diklik
                        if (result.isConfirmed) {
                            // Buka tab baru untuk cetak PDF
                            window.open('../../cetak_tiket.php?id=' + transaksi_id, '_blank');
                            
                            // Reset form
                            $('#formKendaraanMasuk')[0].reset();
                        }
                    });

                } else {
                    // Jika server merespon gagal (misal: plat sudah masuk)
                    Swal.fire({
                        title: 'Gagal!',
                        text: response.message,
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                }
            },
            error: function(xhr, status, error) {
                // Jika terjadi error AJAX (misal: file 404 atau server down)
                Swal.fire({
                    title: 'Error!',
                    text: 'Terjadi kesalahan saat menghubungi server. Silakan coba lagi. ' + error,
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            },
            complete: function() {
                // Kembalikan tampilan tombol seperti semula
                $('#btnText').text('Simpan & Cetak Tiket');
                $('#btnSubmit').prop('disabled', false);
                $('#btnSpinner').addClass('hidden');
            }
        });
    });
});
</script>