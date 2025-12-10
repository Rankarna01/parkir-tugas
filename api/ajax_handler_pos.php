<?php
//=========================================
// AJAX HANDLER UNTUK POS PARKING (BARU)
//=========================================

header('Content-Type: application/json');
require_once '../core/init.php'; // Path ../ karena file ini di /api/

$response = [
    'status' => 'error',
    'message' => 'Aksi tidak dikenal.'
];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Sesi Anda telah habis. Silakan login kembali.';
    echo json_encode($response);
    exit;
}

// Cek aksi apa yang diminta oleh AJAX
if (isset($_POST['action'])) {
    
    //=====================================
    // AKSI: KENDARAAN MASUK MANUAL (dari CCTV box)
    //=====================================
    if ($_POST['action'] == 'kendaraan_masuk_manual') {
        $plat_nomor = strtoupper(trim($_POST['plat_nomor']));
        $jenis_kendaraan = $_POST['jenis_kendaraan'];
        $id_petugas_masuk = $_SESSION['user_id'];
        
        if (empty($plat_nomor) || empty($jenis_kendaraan)) {
            $response['message'] = 'Plat nomor dan jenis kendaraan wajib diisi.';
            echo json_encode($response); exit;
        }

        // Cek duplikasi (jika masih 'masuk')
        $stmt_cek = $db->prepare("SELECT t.id FROM transaksi_parkir t 
                                JOIN kendaraan k ON t.id_kendaraan = k.id 
                                WHERE k.plat_nomor = ? AND t.status = 'masuk'");
        $stmt_cek->bind_param("s", $plat_nomor); $stmt_cek->execute();
        $result_cek = $stmt_cek->get_result();
        if ($result_cek->num_rows > 0) {
            $response['message'] = "Gagal! Kendaraan '$plat_nomor' sudah tercatat masuk.";
            echo json_encode($response); exit;
        }
        $stmt_cek->close();

        // Cari atau Buat data di tabel 'kendaraan' (master)
        $id_kendaraan = null;
        $stmt_find_kendaraan = $db->prepare("SELECT id FROM kendaraan WHERE plat_nomor = ?");
        $stmt_find_kendaraan->bind_param("s", $plat_nomor); $stmt_find_kendaraan->execute();
        $result_kendaraan = $stmt_find_kendaraan->get_result();
        if ($result_kendaraan->num_rows > 0) {
            $id_kendaraan = $result_kendaraan->fetch_assoc()['id'];
        } else {
            $stmt_insert_kendaraan = $db->prepare("INSERT INTO kendaraan (plat_nomor, jenis) VALUES (?, ?)");
            $stmt_insert_kendaraan->bind_param("ss", $plat_nomor, $jenis_kendaraan);
            $stmt_insert_kendaraan->execute();
            $id_kendaraan = $stmt_insert_kendaraan->insert_id;
            $stmt_insert_kendaraan->close();
        }
        $stmt_find_kendaraan->close();

        // Buat data transaksi
        $waktu_masuk = date('Y-m-d H:i:s');
        $kode_barcode_prefix = 'PK-' . date('Ymd') . '-'; 

        $stmt_insert_transaksi = $db->prepare("INSERT INTO transaksi_parkir (id_kendaraan, kode_barcode, waktu_masuk, status, id_petugas_masuk) VALUES (?, '', ?, 'masuk', ?)");
        $stmt_insert_transaksi->bind_param("isi", $id_kendaraan, $waktu_masuk, $id_petugas_masuk);
        
        if ($stmt_insert_transaksi->execute()) {
            $transaksi_id_baru = $stmt_insert_transaksi->insert_id;
            $kode_barcode_final = $kode_barcode_prefix . str_pad($transaksi_id_baru, 5, '0', STR_PAD_LEFT);
            $db->query("UPDATE transaksi_parkir SET kode_barcode = '$kode_barcode_final' WHERE id = $transaksi_id_baru");

            $response['status'] = 'success';
            $response['message'] = 'Kendaraan berhasil dicatat.';
            $response['data'] = [
                'transaksi_id' => $transaksi_id_baru,
                'kode_barcode' => $kode_barcode_final,
                'plat_nomor' => $plat_nomor,
            ];
        } else {
            $response['message'] = 'Gagal menyimpan data transaksi.';
        }
        $stmt_insert_transaksi->close();
    }
    
    //=====================================
    // AKSI: CARI KENDARAAN (UNTUK KELUAR)
    //=====================================
    elseif ($_POST['action'] == 'cari_tiket_atau_plat') {
        $kode_input = trim($_POST['kode_input']);

        if (empty($kode_input)) {
            $response['message'] = 'Input tidak boleh kosong.';
            echo json_encode($response); exit;
        }

        // Query join (CARI BERDASARKAN KODE TIKET ATAU PLAT NOMOR)
        $stmt = $db->prepare(
            "SELECT 
                t.id AS transaksi_id, t.waktu_masuk,
                k.plat_nomor, k.jenis,
                tar.tarif_per_jam
             FROM transaksi_parkir t
             JOIN kendaraan k ON t.id_kendaraan = k.id
             LEFT JOIN tarif_parkir tar ON k.jenis = tar.jenis_kendaraan
             WHERE (t.kode_barcode = ? OR k.plat_nomor = ?) AND t.status = 'masuk'"
        );
        $stmt->bind_param("ss", $kode_input, $kode_input);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 0) {
            $response['message'] = "Tiket/Plat tidak ditemukan atau kendaraan sudah keluar.";
            echo json_encode($response); exit;
        }

        $data = $result->fetch_assoc();

        // --- Logika Perhitungan Biaya ---
        $waktu_masuk = new DateTime($data['waktu_masuk']);
        $waktu_sekarang = new DateTime();
        $durasi_detik = $waktu_sekarang->getTimestamp() - $waktu_masuk->getTimestamp();
        $total_jam = ceil($durasi_detik / 3600);
        if ($total_jam <= 0) $total_jam = 1;

        $tarif_per_jam = (float) $data['tarif_per_jam'];
        $total_biaya = $total_jam * $tarif_per_jam;

        $durasi = $waktu_sekarang->diff($waktu_masuk);
        $durasi_format = $durasi->d . ' hari, ' . $durasi->h . ' jam, ' . $durasi->i . ' mnt';
        
        $response['status'] = 'success';
        $response['data'] = [
            'transaksi_id' => $data['transaksi_id'],
            'plat_nomor' => $data['plat_nomor'],
            'jenis' => ucfirst($data['jenis']),
            'waktu_masuk_format' => date('d M Y, H:i:s', strtotime($data['waktu_masuk'])),
            'durasi_format' => $durasi_format,
            'total_biaya' => $total_biaya,
            'total_biaya_format' => number_format($total_biaya, 0, ',', '.')
        ];
        $stmt->close();
    }
    
    //=====================================
    // AKSI: PROSES KENDARAAN KELUAR
    //=====================================
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
                $response['transaksi_id'] = $transaksi_id;
            } else {
                $response['message'] = 'Data transaksi tidak ditemukan (mungkin sudah diproses).';
            }
        } else {
            $response['message'] = 'Gagal memperbarui data.';
        }
        $stmt->close();
    }

}

// Kembalikan respon sebagai JSON
echo json_encode($response);
$db->close();
exit;
?>