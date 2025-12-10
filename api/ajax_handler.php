<?php
//=========================================
// AJAX HANDLER
// File ini memproses data dari JavaScript
// dan mengembalikan data dalam format JSON
//=========================================

// Set header agar browser tahu ini adalah JSON
header('Content-Type: application/json');

// Memanggil file init (untuk $db dan $_SESSION)
// Path-nya ../ karena file ini ada di dalam folder 'api/'
require_once '../core/init.php';

// Siapkan array untuk respon
$response = [
    'status' => 'error',
    'message' => 'Aksi tidak dikenal.'
];

// Cek apakah user login (keamanan)
if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Sesi Anda telah habis. Silakan login kembali.';
    echo json_encode($response);
    exit;
}

// Cek aksi apa yang diminta oleh AJAX
if (isset($_POST['action'])) {
    
    //-------------------------------------
    // AKSI: KENDARAAN MASUK
    //-------------------------------------
    if ($_POST['action'] == 'kendaraan_masuk') {
        $plat_nomor = strtoupper(trim($_POST['plat_nomor']));
        $jenis_kendaraan = $_POST['jenis_kendaraan'];
        $id_petugas_masuk = $_SESSION['user_id'];
        
        // 1. Validasi Sederhana
        if (empty($plat_nomor) || empty($jenis_kendaraan)) {
            $response['message'] = 'Plat nomor dan jenis kendaraan wajib diisi.';
            echo json_encode($response);
            exit;
        }

        // 2. Cek apakah kendaraan ini MASIH DI DALAM
        $stmt_cek = $db->prepare("SELECT t.id FROM transaksi_parkir t 
                                JOIN kendaraan k ON t.id_kendaraan = k.id 
                                WHERE k.plat_nomor = ? AND t.status = 'masuk'");
        $stmt_cek->bind_param("s", $plat_nomor);
        $stmt_cek->execute();
        $result_cek = $stmt_cek->get_result();

        if ($result_cek->num_rows > 0) {
            $response['message'] = "Gagal! Kendaraan dengan plat '$plat_nomor' sudah tercatat masuk dan belum keluar.";
            echo json_encode($response);
            $stmt_cek->close();
            $db->close();
            exit;
        }
        $stmt_cek->close();

        // 3. Cek data di tabel 'kendaraan' (master)
        $id_kendaraan = null;
        $stmt_find_kendaraan = $db->prepare("SELECT id FROM kendaraan WHERE plat_nomor = ?");
        $stmt_find_kendaraan->bind_param("s", $plat_nomor);
        $stmt_find_kendaraan->execute();
        $result_kendaraan = $stmt_find_kendaraan->get_result();
        
        if ($result_kendaraan->num_rows > 0) {
            // Kendaraan sudah ada di master
            $data_kendaraan = $result_kendaraan->fetch_assoc();
            $id_kendaraan = $data_kendaraan['id'];
        } else {
            // Kendaraan baru, masukkan ke master
            $stmt_insert_kendaraan = $db->prepare("INSERT INTO kendaraan (plat_nomor, jenis) VALUES (?, ?)");
            $stmt_insert_kendaraan->bind_param("ss", $plat_nomor, $jenis_kendaraan);
            $stmt_insert_kendaraan->execute();
            $id_kendaraan = $stmt_insert_kendaraan->insert_id;
            $stmt_insert_kendaraan->close();
        }
        $stmt_find_kendaraan->close();

        // 4. Buat Kode Barcode & Waktu
        $waktu_masuk = date('Y-m-d H:i:s');
        $kode_barcode_prefix = 'PK-' . date('Ymd') . '-'; // Format tiket

        // 5. Masukkan ke tabel 'transaksi_parkir'
        $stmt_insert_transaksi = $db->prepare(
            "INSERT INTO transaksi_parkir 
             (id_kendaraan, kode_barcode, waktu_masuk, status, id_petugas_masuk) 
             VALUES (?, '', ?, 'masuk', ?)" // Kode barcode dikosongkan dulu
        );
        $stmt_insert_transaksi->bind_param("isi", $id_kendaraan, $waktu_masuk, $id_petugas_masuk);
        
        if ($stmt_insert_transaksi->execute()) {
            
            // Ambil ID transaksi yang baru saja di-insert
            $transaksi_id_baru = $stmt_insert_transaksi->insert_id;
            
            // Buat kode barcode final
            $kode_barcode_final = $kode_barcode_prefix . str_pad($transaksi_id_baru, 5, '0', STR_PAD_LEFT);
            
            // Update kode barcode di tabel
            $db->query("UPDATE transaksi_parkir SET kode_barcode = '$kode_barcode_final' WHERE id = $transaksi_id_baru");

            $response['status'] = 'success';
            $response['message'] = 'Kendaraan berhasil dicatat.';
            $response['data'] = [
                'transaksi_id' => $transaksi_id_baru, // Kirim ID untuk cetak PDF
                'kode_barcode' => $kode_barcode_final,
                'plat_nomor' => $plat_nomor,
                'waktu_masuk' => date('d M Y, H:i:s', strtotime($waktu_masuk))
            ];
        } else {
            $response['message'] = 'Gagal menyimpan data transaksi. Error: ' . $db->error;
        }
        $stmt_insert_transaksi->close();
    }
    
    //-------------------------------------
    // AKSI: CARI KENDARAAN (UNTUK KELUAR)
    //-------------------------------------
    elseif ($_POST['action'] == 'cari_kendaraan') {
        $kode_tiket = trim($_POST['kode_tiket']);

        if (empty($kode_tiket)) {
            $response['message'] = 'Kode tiket tidak boleh kosong.';
            echo json_encode($response);
            exit;
        }

        // Query join untuk ambil data lengkap
        $stmt = $db->prepare(
            "SELECT 
                t.id AS transaksi_id, t.waktu_masuk,
                k.plat_nomor, k.jenis,
                tar.tarif_per_jam
             FROM transaksi_parkir t
             JOIN kendaraan k ON t.id_kendaraan = k.id
             LEFT JOIN tarif_parkir tar ON k.jenis = tar.jenis_kendaraan
             WHERE t.kode_barcode = ? AND t.status = 'masuk'"
        );
        $stmt->bind_param("s", $kode_tiket);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 0) {
            $response['message'] = "Tiket tidak ditemukan atau kendaraan sudah keluar.";
            echo json_encode($response);
            exit;
        }

        $data = $result->fetch_assoc();

        // --- Logika Perhitungan Biaya ---
        $waktu_masuk = new DateTime($data['waktu_masuk']);
        $waktu_sekarang = new DateTime();
        
        $durasi_detik = $waktu_sekarang->getTimestamp() - $waktu_masuk->getTimestamp();
        
        // Hitung total jam, bulatkan KE ATAS (ceil)
        // 3600 detik = 1 jam
        $total_jam = ceil($durasi_detik / 3600);
        
        // Jika parkir kurang dari 1 jam (misal 10 menit), tetap dihitung 1 jam
        if ($total_jam <= 0) {
            $total_jam = 1;
        }

        $tarif_per_jam = (float) $data['tarif_per_jam'];
        $total_biaya = $total_jam * $tarif_per_jam;

        // Hitung durasi format (Jam, Menit)
        $durasi = $waktu_sekarang->diff($waktu_masuk);
        $durasi_format = $durasi->d . ' hari, ' . $durasi->h . ' jam, ' . $durasi->i . ' menit';
        
        // Kirim respon sukses
        $response['status'] = 'success';
        $response['data'] = [
            'transaksi_id' => $data['transaksi_id'],
            'plat_nomor' => $data['plat_nomor'],
            'jenis' => ucfirst($data['jenis']),
            'waktu_masuk' => $data['waktu_masuk'],
            'waktu_masuk_format' => date('d M Y, H:i:s', strtotime($data['waktu_masuk'])),
            'waktu_sekarang_format' => $waktu_sekarang->format('d M Y, H:i:s'),
            'durasi_format' => $durasi_format,
            'total_jam' => $total_jam,
            'tarif_per_jam' => $tarif_per_jam,
            'tarif_per_jam_format' => number_format($tarif_per_jam, 0, ',', '.'),
            'total_biaya' => $total_biaya,
            'total_biaya_format' => number_format($total_biaya, 0, ',', '.')
        ];
        $stmt->close();
    }
    
    //-------------------------------------
    // AKSI: PROSES KENDARAAN KELUAR
    //-------------------------------------
    elseif ($_POST['action'] == 'proses_keluar') {
        $transaksi_id = $_POST['transaksi_id'];
        $total_biaya = $_POST['total_biaya'];
        $id_petugas_keluar = $_SESSION['user_id'];
        $waktu_keluar = date('Y-m-d H:i:s');

        $stmt = $db->prepare(
            "UPDATE transaksi_parkir 
             SET status = 'keluar', waktu_keluar = ?, biaya = ?, id_petugas_keluar = ? 
             WHERE id = ? AND status = 'masuk'"
        );
        $stmt->bind_param("sdii", $waktu_keluar, $total_biaya, $id_petugas_keluar, $transaksi_id);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $response['status'] = 'success';
                $response['message'] = 'Pembayaran berhasil disimpan.';
                $response['transaksi_id'] = $transaksi_id; // Kirim balik ID untuk cetak struk
            } else {
                $response['message'] = 'Data transaksi tidak ditemukan atau sudah diproses.';
            }
        } else {
            $response['message'] = 'Gagal memperbarui data. Error: ' . $db->error;
        }
        $stmt->close();
    }

}

// Kembalikan respon sebagai JSON
echo json_encode($response);
$db->close();
exit;
?>