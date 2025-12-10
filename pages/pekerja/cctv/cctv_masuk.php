<?php
?>
<div class="bg-white rounded-xl shadow-lg overflow-hidden">
    <div class="bg-gray-800 text-white px-4 py-2 font-semibold">
        <i class="fas fa-video mr-2"></i> CCTV Gerbang Masuk
    </div>

    <div class="p-4 bg-black h-48 flex items-center justify-center">
        <p class="text-gray-500">Waiting for stream...</p>
        <img src="http://IP_KAMERA_1/stream.mjpg" alt="CCTV Masuk" class="h-48 object-cover rounded-lg" />
    </div>

    <div class="p-4 border-t border-gray-200">
        <h4 class="text-lg font-semibold text-gray-800 mb-3">Input Kendaraan Masuk</h4>
        <form id="formKendaraanMasuk">
            <input type="hidden" name="action" value="kendaraan_masuk_manual">

            <div class="mb-3">
                <label for="plat_nomor_manual" class="block text-sm font-medium text-gray-700">Plat Nomor</label>
                <input type="text" id="plat_nomor_manual" name="plat_nomor"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 uppercase"
                    placeholder="BK 1234 ABC" required>
            </div>

            <div class="mb-4">
                <label for="jenis_kendaraan_manual" class="block text-sm font-medium text-gray-700">Jenis Kendaraan</label>
                <select id="jenis_kendaraan_manual" name="jenis_kendaraan"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" required>
                    <option value="">Pilih Jenis</option>
                    <?php if (!empty($tarif_options)): ?>
                        <?php foreach ($tarif_options as $tarif): ?>
                            <option value="<?php echo $tarif['jenis_kendaraan']; ?>">
                                <?php echo ucfirst($tarif['jenis_kendaraan']); ?>
                            </option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <option value="" disabled>Error: Tarif tidak dimuat</option>
                    <?php endif; ?>
                </select>
            </div>

            <button type="submit" id="btnSubmitManual" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg flex items-center justify-center">
                <i id="iconSubmitManual" class="fas fa-save mr-2"></i>
                <span id="textSubmitManual">Simpan & Cetak Tiket</span>
                <i id="spinnerSubmitManual" class="fas fa-spinner fa-spin ml-2 hidden"></i>
            </button>
        </form>
    </div>
</div>

<script>
    $(document).ready(function() {

        $('#formKendaraanMasuk').on('submit', function(e) {
            e.preventDefault();


            $('#textSubmitManual').text('Menyimpan...');
            $('#btnSubmitManual').prop('disabled', true);
            $('#iconSubmitManual').addClass('hidden');
            $('#spinnerSubmitManual').removeClass('hidden');

            $.ajax({
                type: 'POST',
                url: '../../api/ajax_handler_pos.php',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(response) {
                    if (response.status == 'success') {
                        var transaksi_id = response.data.transaksi_id;
                        Swal.fire({
                            title: 'Berhasil Masuk!',
                            html: '<strong>Plat:</strong> ' + response.data.plat_nomor + '<br>' +
                                '<strong>Kode Tiket:</strong> ' + response.data.kode_barcode,
                            icon: 'success',
                            confirmButtonText: 'OK & Cetak Tiket'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                window.open('../../cetak_tiket.php?id=' + transaksi_id, '_blank');
                            }
                            $('#formKendaraanMasuk')[0].reset();
                        });
                    } else {
                        Swal.fire('Gagal!', response.message, 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error!', 'Tidak bisa terhubung ke server.', 'error');
                },
                complete: function() {

                    $('#textSubmitManual').text('Simpan & Cetak Tiket');
                    $('#btnSubmitManual').prop('disabled', false);
                    $('#iconSubmitManual').removeClass('hidden');
                    $('#spinnerSubmitManual').addClass('hidden');
                }
            });
        });
    });
</script>