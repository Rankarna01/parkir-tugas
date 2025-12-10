<?php
//=========================================
// LOGIKA PHP (BAGIAN ATAS)
//=========================================
$page_title = "Kendaraan Keluar";
require_once '../../core/init.php';

// Keamanan Halaman
if ($_SESSION['role'] != 'pekerja') {
    header('Location: ../../login.php');
    exit;
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

            <div id="searchBlock" class="bg-white p-8 rounded-lg shadow-md">
                <h2 class="text-2xl font-semibold text-gray-800 mb-6">Cari Tiket Kendaraan</h2>
                <form id="formCariTiket">
                    <input type="hidden" name="action" value="cari_kendaraan">
                    <div class="mb-4">
                        <label for="kode_tiket" class="block text-sm font-medium text-gray-700 mb-2">Kode Tiket / Barcode</label>
                        <input type="text" id="kode_tiket" name="kode_tiket"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg text-lg focus:ring-blue-500 focus:border-blue-500"
                               placeholder="Scan atau ketik kode tiket..." required>
                    </div>
                    <button type="submit" id="btnCari"
                            class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-4 rounded-lg transition duration-150 ease-in-out flex items-center justify-center">
                        <i class="fas fa-search mr-2"></i>
                        <span id="btnCariText">Cari Kendaraan</span>
                        <i id="btnCariSpinner" class="fas fa-spinner fa-spin ml-2 hidden"></i>
                    </button>
                </form>
            </div>

            <div id="resultBlock" class="bg-white p-8 rounded-lg shadow-md hidden">
                <h2 class="text-2xl font-semibold text-gray-800 mb-6">Konfirmasi Pembayaran</h2>
                
                <div class="space-y-3 mb-6">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Plat Nomor:</span>
                        <span id="detail_plat_nomor" class="font-bold text-gray-800 text-lg"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Jenis Kendaraan:</span>
                        <span id="detail_jenis" class="font-medium text-gray-800"></span>
                    </div>
                    <hr>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Waktu Masuk:</span>
                        <span id="detail_waktu_masuk" class="font-medium text-gray-800"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Waktu Keluar:</span>
                        <span id="detail_waktu_keluar" class="font-medium text-gray-800"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Durasi Parkir:</span>
                        <span id="detail_durasi" class="font-bold text-gray-800"></span>
                    </div>
                    <hr>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Tarif / Jam:</span>
                        <span id="detail_tarif" class="font-medium text-gray-800"></span>
                    </div>
                    <div class="flex justify-between items-center mt-4">
                        <span class="text-2xl font-bold text-gray-900">Total Biaya:</span>
                        <span id="detail_total_biaya" class="text-3xl font-extrabold text-blue-600"></span>
                    </div>
                </div>

                <form id="formKonfirmasi">
                    <input type="hidden" name="action" value="proses_keluar">
                    <input type="hidden" id="hidden_transaksi_id" name="transaksi_id">
                    <input type="hidden" id="hidden_total_biaya" name="total_biaya">
                </form>

                <div class="flex space-x-4">
                    <button type="button" id="btnBatal"
                            class="w-1/3 bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-3 px-4 rounded-lg transition duration-150">
                        <i class="fas fa-times mr-2"></i> Batal
                    </button>
                    <button type="button" id="btnKonfirmasi"
                            class="w-2/3 bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg transition duration-150 flex items-center justify-center">
                        <i class="fas fa-check-circle mr-2"></i>
                        <span id="btnKonfirmasiText">Konfirmasi Pembayaran</span>
                        <i id="btnKonfirmasiSpinner" class="fas fa-spinner fa-spin ml-2 hidden"></i>
                    </button>
                </div>
            </div>

        </div>
    </main>
</div>

<?php require_once '../../templates/footer_app.php'; // Footer ?>

<script>
$(document).ready(function() {
    
    // --- AKSI 1: MENCARI TIKET ---
    $('#formCariTiket').on('submit', function(e) {
        e.preventDefault();
        
        // Tampilkan loading
        $('#btnCariText').text('Mencari...');
        $('#btnCariSpinner').removeClass('hidden');
        $('#btnCari').prop('disabled', true);

        $.ajax({
            type: 'POST',
            url: '../../api/ajax_handler.php',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.status == 'success') {
                    // Isi data ke Blok 2
                    $('#detail_plat_nomor').text(response.data.plat_nomor);
                    $('#detail_jenis').text(response.data.jenis);
                    $('#detail_waktu_masuk').text(response.data.waktu_masuk_format);
                    $('#detail_waktu_keluar').text(response.data.waktu_sekarang_format);
                    $('#detail_durasi').text(response.data.durasi_format);
                    $('#detail_tarif').text('Rp ' + response.data.tarif_per_jam_format);
                    $('#detail_total_biaya').text('Rp ' + response.data.total_biaya_format);

                    // Simpan data untuk dikirim di Aksi 2
                    $('#hidden_transaksi_id').val(response.data.transaksi_id);
                    $('#hidden_total_biaya').val(response.data.total_biaya);

                    // Tukar tampilan Blok
                    $('#searchBlock').addClass('hidden');
                    $('#resultBlock').removeClass('hidden');
                } else {
                    Swal.fire('Gagal!', response.message, 'error');
                }
            },
            error: function() {
                Swal.fire('Error!', 'Tidak bisa terhubung ke server.', 'error');
            },
            complete: function() {
                // Hentikan loading
                $('#btnCariText').text('Cari Kendaraan');
                $('#btnCariSpinner').addClass('hidden');
                $('#btnCari').prop('disabled', false);
            }
        });
    });

    // --- AKSI 2: KONFIRMASI PEMBAYARAN ---
    $('#btnKonfirmasi').on('click', function() {
        // Tampilkan loading
        $('#btnKonfirmasiText').text('Memproses...');
        $('#btnKonfirmasiSpinner').removeClass('hidden');
        $('#btnKonfirmasi').prop('disabled', true);
        $('#btnBatal').prop('disabled', true);

        $.ajax({
            type: 'POST',
            url: '../../api/ajax_handler.php',
            data: $('#formKonfirmasi').serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.status == 'success') {
                    var transaksi_id = response.transaksi_id;
                    Swal.fire({
                        title: 'Pembayaran Berhasil!',
                        text: 'Palang parkir terbuka. Ingin cetak struk?',
                        icon: 'success',
                        showCancelButton: true,
                        confirmButtonText: 'Tutup Saja',
                        cancelButtonText: 'Cetak Struk PDF',
                        cancelButtonColor: '#3085d6',
                    }).then((result) => {
                        if (result.dismiss === Swal.DismissReason.cancel) {
                            // Jika klik "Cetak Struk PDF", buka tab baru
                            window.open('../../cetak_struk.php?id=' + transaksi_id, '_blank');
                        }
                        // Reset halaman
                        resetHalaman();
                    });
                } else {
                    Swal.fire('Gagal!', response.message, 'error');
                }
            },
            error: function() {
                Swal.fire('Error!', 'Tidak bisa terhubung ke server.', 'error');
            },
            complete: function() {
                // Hentikan loading
                $('#btnKonfirmasiText').text('Konfirmasi Pembayaran');
                $('#btnKonfirmasiSpinner').addClass('hidden');
                $('#btnKonfirmasi').prop('disabled', false);
                $('#btnBatal').prop('disabled', false);
            }
        });
    });

    // --- AKSI 3: BATAL ---
    $('#btnBatal').on('click', function() {
        resetHalaman();
    });
    
    function resetHalaman() {
        // Tukar tampilan Blok
        $('#resultBlock').addClass('hidden');
        $('#searchBlock').removeClass('hidden');
        
        // Kosongkan form
        $('#formCariTiket')[0].reset();
        $('#formKonfirmasi')[0].reset();
    }
});
</script>