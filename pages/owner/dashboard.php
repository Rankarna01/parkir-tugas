<?php
//=========================================
// LOGIKA PHP (BAGIAN ATAS)
//=========================================
$page_title = "Dashboard Owner";
require_once '../../core/init.php';

// Keamanan Halaman
// Memastikan hanya 'owner' yang bisa mengakses
if ($_SESSION['role'] != 'owner') {
    header('Location: ../../login.php');
    exit;
}

// 1. Ambil Jumlah Pekerja
$result_pekerja = $db->query("SELECT COUNT(id) AS total_pekerja FROM users WHERE role = 'pekerja'");
$data_pekerja = $result_pekerja->fetch_assoc();
$total_pekerja = $data_pekerja['total_pekerja'];

// 2. Ambil Pendapatan Hari Ini
$today = date('Y-m-d');
$result_pendapatan = $db->query("SELECT SUM(biaya) AS total_pendapatan FROM transaksi_parkir WHERE status = 'keluar' AND DATE(waktu_keluar) = '$today'");
$data_pendapatan = $result_pendapatan->fetch_assoc();
$total_pendapatan_hari_ini = $data_pendapatan['total_pendapatan'] ?? 0;

// 3. Ambil Kendaraan Masuk Hari Ini
$result_masuk = $db->query("SELECT COUNT(id) AS total_masuk FROM transaksi_parkir WHERE DATE(waktu_masuk) = '$today'");
$data_masuk = $result_masuk->fetch_assoc();
$total_kendaraan_masuk_hari_ini = $data_masuk['total_masuk'];

$result_keluar = $db->query("SELECT COUNT(id) AS total_keluar FROM transaksi_parkir WHERE status = 'keluar' AND DATE(waktu_keluar) = '$today'");
$data_keluar = $result_keluar->fetch_assoc();
$total_kendaraan_keluar_hari_ini = $data_keluar['total_keluar'];

// 4. Ambil Total Kendaraan YANG MASIH DI DALAM
$result_didalam = $db->query("SELECT COUNT(id) AS total_didalam FROM transaksi_parkir WHERE status = 'masuk'");
$data_didalam = $result_didalam->fetch_assoc();
$total_kendaraan_didalam = $data_didalam['total_didalam'];

//=========================================
// TAMPILAN HTML (BAGIAN BAWAH)
//=========================================
?>

<?php require_once '../../templates/header_app.php'; // Header ?>
<?php require_once '../../templates/sidebar.php'; // Sidebar ?>

<div class="flex-1 flex flex-col overflow-hidden">

    <?php require_once '../../templates/navbar_app.php'; // Navbar ?>

    <!-- Background selaras tema biru -->
    <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gradient-to-br from-blue-50 via-white to-blue-100 p-6">
        <div class="container mx-auto">

            <!-- Hero / Greeting -->
            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-blue-700 via-blue-800 to-blue-900 p-6 mb-6 shadow-xl">
                <div class="absolute inset-0 opacity-20"
                     style="background: radial-gradient(800px 300px at 10% -10%, rgba(255,255,255,.35), transparent),
                              radial-gradient(600px 200px at 90% 120%, rgba(59,130,246,.35), transparent);"></div>
                <div class="relative flex items-center">
                    <div class="bg-white/10 backdrop-blur rounded-xl p-3 shadow-md">
                        <i class="fas fa-parking text-3xl text-blue-200"></i>
                    </div>
                    <div class="ml-4">
                        <h2 class="text-2xl md:text-3xl font-bold text-white">Selamat Datang, <?php echo htmlspecialchars($_SESSION['nama']); ?>!</h2>
                        <p class="text-blue-100">Ringkasan aktivitas parkir hari ini.</p>
                    </div>
                </div>
            </div>

            <!-- KPI Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">

                <!-- Pendapatan -->
                <div class="group relative overflow-hidden bg-white rounded-2xl shadow-lg p-6 border border-blue-100">
                    <div class="absolute -right-8 -top-8 w-32 h-32 rounded-full bg-blue-100 group-hover:scale-125 transition"></div>
                    <div class="relative flex items-center">
                        <div class="bg-blue-600 text-white p-4 rounded-2xl shadow-md">
                            <i class="fas fa-wallet fa-lg"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-xs uppercase tracking-wider text-blue-600 font-semibold">Pendapatan Hari Ini</p>
                            <p class="text-2xl font-extrabold text-gray-900 leading-tight">
                                Rp <span id="moneyCount"><?php echo number_format($total_pendapatan_hari_ini, 0, ',', '.'); ?></span>
                            </p>
                        </div>
                    </div>
                    <div class="mt-4">
                        <!-- Doughnut: Pendapatan vs Target -->
                        <canvas id="chartRevenue" height="120"></canvas>
                       
                    </div>
                </div>

                <!-- Kendaraan Masuk -->
                <div class="group relative overflow-hidden bg-white rounded-2xl shadow-lg p-6 border border-blue-100">
    <div class="absolute -right-8 -top-8 w-32 h-32 rounded-full bg-blue-100 group-hover:scale-125 transition"></div>

    <div class="relative flex items-center">
        <div class="bg-blue-500 text-white p-4 rounded-2xl shadow-md">
            <i class="fas fa-arrow-down fa-lg"></i>
        </div>
        <div class="ml-4">
            <p class="text-xs uppercase tracking-wider text-blue-600 font-semibold">Kendaraan Masuk (Hari Ini)</p>
            <p class="text-2xl font-extrabold text-gray-900">
                <span id="masukCount"><?php echo (int)$total_kendaraan_masuk_hari_ini; ?></span>
                <span class="text-base font-medium text-gray-500">unit</span>
            </p>
        </div>
    </div>

    <!-- Progress mini -->
    <div class="mt-4">
        <div class="h-2 w-full bg-blue-100 rounded-full overflow-hidden">
            <div id="barMasuk" class="h-full bg-blue-500 rounded-full" style="width:0%"></div>
        </div>
        <p class="text-xs text-gray-500 mt-2">Progress terhadap target harian.</p>
    </div>

    <!-- Tambahan: Kendaraan Keluar -->
    <div class="relative flex items-center mt-5 border-t border-blue-100 pt-4">
        <div class="bg-green-500 text-white p-4 rounded-2xl shadow-md">
            <i class="fas fa-arrow-up fa-lg"></i>
        </div>
        <div class="ml-4">
            <p class="text-xs uppercase tracking-wider text-green-600 font-semibold">Kendaraan Keluar (Hari Ini)</p>
            <p class="text-2xl font-extrabold text-gray-900">
                <?php echo (int)$total_kendaraan_keluar_hari_ini; ?>
                <span class="text-base font-medium text-gray-500">unit</span>
            </p>
        </div>
    </div>
</div>


                <!-- Kendaraan di Dalam -->
                <div class="group relative overflow-hidden bg-white rounded-2xl shadow-lg p-6 border border-blue-100">
                    <div class="absolute -right-8 -top-8 w-32 h-32 rounded-full bg-blue-100 group-hover:scale-125 transition"></div>
                    <div class="relative flex items-center">
                        <div class="bg-indigo-500 text-white p-4 rounded-2xl shadow-md">
                            <i class="fas fa-car fa-lg"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-xs uppercase tracking-wider text-blue-600 font-semibold">Masih Parkir</p>
                            <p class="text-2xl font-extrabold text-gray-900">
                                <span id="didalamCount"><?php echo (int)$total_kendaraan_didalam; ?></span>
                                <span class="text-base font-medium text-gray-500">unit</span>
                            </p>
                        </div>
                    </div>
                    <div class="mt-4">
                        <canvas id="chartGaugeInside" height="120"></canvas>
                        <p class="text-xs text-gray-500 mt-2">Perbandingan terhadap kapasitas asumsi.</p>
                    </div>
                </div>

                <!-- Pekerja Aktif -->
                <div class="group relative overflow-hidden bg-white rounded-2xl shadow-lg p-6 border border-blue-100">
                    <div class="absolute -right-8 -top-8 w-32 h-32 rounded-full bg-blue-100 group-hover:scale-125 transition"></div>
                    <div class="relative flex items-center">
                        <div class="bg-purple-600 text-white p-4 rounded-2xl shadow-md">
                            <i class="fas fa-users fa-lg"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-xs uppercase tracking-wider text-blue-600 font-semibold">Pekerja Aktif</p>
                            <p class="text-2xl font-extrabold text-gray-900">
                                <span id="pekerjaCount"><?php echo (int)$total_pekerja; ?></span>
                                <span class="text-base font-medium text-gray-500">orang</span>
                            </p>
                        </div>
                    </div>
                    <div class="mt-4">
                        <div class="h-2 w-full bg-purple-100 rounded-full overflow-hidden">
                            <div id="barPekerja" class="h-full bg-purple-600 rounded-full" style="width:0%"></div>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">Komposisi terhadap kebutuhan shift (asumsi).</p>
                    </div>
                </div>
            </div>

            <!-- Area Grafik KPI -->
            <div class="mt-8 grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Bar Komparatif -->
                <div class="lg:col-span-2 bg-white rounded-2xl shadow-lg border border-blue-100 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-xl font-bold text-gray-800">Grafik trend</h3>
                        <span class="text-xs px-2 py-1 rounded-full bg-blue-50 text-blue-700 border border-blue-200">Live</span>
                    </div>
                    <canvas id="chartCompare" height="120"></canvas>
                </div>

                <!-- Line efek “pulse” -->
                <div class="bg-white rounded-2xl shadow-lg border border-blue-100 p-6">
                    <h3 class="text-xl font-bold text-gray-800 mb-4">Tren Indeks Aktivitas</h3>
                    <canvas id="chartPulse" height="120"></canvas>
                    
                </div>
            </div>
        </div>
    </main>
</div>

<?php require_once '../../templates/footer_app.php'; // Footer ?>

<!-- Chart.js CDN (hanya front-end, tidak mengubah fungsi/backend) -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
/* ===============================
   Ambil data dari PHP (apa adanya)
   =============================== */
const pendapatan = <?php echo (float)($total_pendapatan_hari_ini ?? 0); ?>;
const masuk = <?php echo (int)$total_kendaraan_masuk_hari_ini; ?>;
const didalam = <?php echo (int)$total_kendaraan_didalam; ?>;
const pekerja = <?php echo (int)$total_pekerja; ?>;

/* ============================================
   Target/kapasitas (front-end only, bisa diganti)
   ============================================ */
const TARGET_PENDAPATAN = 5000000; // contoh target harian Rp 5.000.000
const TARGET_MASUK = 120;          // contoh target unit/hari
const KAPASITAS_PARKIR = 200;      // contoh kapasitas total
const KEBUTUHAN_SHIFT = 10;        // contoh kebutuhan pekerja aktif

/* ==============
   Utility kecil
   ============== */
function animateWidth(el, percent, duration = 800) {
  const start = 0;
  const startTime = performance.now();
  function tick(now) {
    const p = Math.min((now - startTime) / duration, 1);
    const eased = 1 - Math.pow(1 - p, 3); // easeOutCubic
    el.style.width = (start + (percent - start) * eased) + '%';
    if (p < 1) requestAnimationFrame(tick);
  }
  requestAnimationFrame(tick);
}

/* ==================
   Progress Bar anim
   ================== */
const barMasuk = document.getElementById('barMasuk');
const barPekerja = document.getElementById('barPekerja');
animateWidth(barMasuk, Math.min(100, (masuk / TARGET_MASUK) * 100));
animateWidth(barPekerja, Math.min(100, (pekerja / KEBUTUHAN_SHIFT) * 100));

/* =========================
   Doughnut: Revenue vs Target
   ========================= */
new Chart(document.getElementById('chartRevenue'), {
  type: 'doughnut',
  data: {
    labels: ['Tercapai', 'Sisa Target'],
    datasets: [{
      data: [Math.max(0, Math.min(pendapatan, TARGET_PENDAPATAN)), Math.max(0, TARGET_PENDAPATAN - pendapatan)],
      borderWidth: 0,
      hoverOffset: 8
    }]
  },
  options: {
    cutout: '70%',
    responsive: true,
    plugins: {
      legend: { display: false },
      tooltip: {
        callbacks: {
          label: (ctx) => {
            const val = ctx.parsed;
            return ctx.label + ': Rp ' + val.toLocaleString('id-ID');
          }
        }
      }
    },
    animation: { animateRotate: true, animateScale: true }
  }
});

/* ==================================
   Gauge-like (semi-doughnut) Inside
   ================================== */
new Chart(document.getElementById('chartGaugeInside'), {
  type: 'doughnut',
  data: {
    labels: ['Terisi', 'Kosong'],
    datasets: [{
      data: [Math.min(didalam, KAPASITAS_PARKIR), Math.max(0, KAPASITAS_PARKIR - didalam)],
      borderWidth: 0
    }]
  },
  options: {
    circumference: 180,
    rotation: -90,
    cutout: '70%',
    plugins: { legend: { display: false } },
    animation: { animateRotate: true, animateScale: true }
  }
});

/* =========================
   Bar Komparatif KPI Harian
   ========================= */
new Chart(document.getElementById('chartCompare'), {
  type: 'bar',
  data: {
    labels: ['Masuk (unit)', 'Di Dalam (unit)', 'Pekerja (org)'],
    datasets: [{
      label: 'Jumlah',
      data: [masuk, didalam, pekerja],
      borderWidth: 0
    }]
  },
  options: {
    responsive: true,
    plugins: {
      legend: { display: false },
      tooltip: { enabled: true }
    },
    scales: {
      x: { grid: { display: false } },
      y: { grid: { color: 'rgba(59,130,246,0.1)' }, beginAtZero: true }
    },
    animation: { duration: 900, easing: 'easeOutCubic' }
  }
});

/* =========================================
   Line “Pulse” – indeks gabungan sederhana
   (murni dari data yang ada, untuk efek hidup)
   ========================================= */
const idx = [
  Math.round((masuk/Math.max(TARGET_MASUK,1))*60 + (didalam/Math.max(KAPASITAS_PARKIR,1))*30 + (pekerja/Math.max(KEBUTUHAN_SHIFT,1))*10),
];
/* buat garis 8 titik untuk animasi halus: start rendah -> naik ke indeks */
const pulsePoints = [10, 18, 25, 32, 45, 55, 62, Math.min(100, idx[0])];

new Chart(document.getElementById('chartPulse'), {
  type: 'line',
  data: {
    labels: ['06:00','08:00','10:00','12:00','14:00','16:00','18:00','Sekarang'],
    datasets: [{
      label: 'Indeks Aktivitas',
      data: pulsePoints,
      tension: 0.35,
      pointRadius: 0,
      fill: true
    }]
  },
  options: {
    plugins: { legend: { display: false } },
    scales: {
      x: { grid: { display: false } },
      y: { grid: { color: 'rgba(59,130,246,0.08)' }, beginAtZero: true, max: 100 }
    },
    animation: { duration: 1200, easing: 'easeOutQuart' }
  }
});
</script>
