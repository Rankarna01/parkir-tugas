<?php
// templates/navbar_app.php
$user_nama = $_SESSION['nama'] ?? 'Pengguna';
?>

<!-- Header Navbar -->
<header class="bg-gradient-to-r from-blue-800 via-blue-700 to-blue-600 shadow-md px-6 py-3 flex justify-between items-center text-white relative z-20">

    <!-- Kiri: Judul atau ikon sistem -->
    <div class="flex items-center space-x-3">
        <div class="bg-blue-500 p-2 rounded-lg shadow-md">
            <i class="fas fa-parking text-xl text-white"></i>
        </div>
        <h1 class="text-lg md:text-xl font-semibold tracking-wide">Sistem Manajemen Parkir</h1>
    </div>

    <!-- Tengah: Jam Realtime -->
    <div id="realtimeClock" class="text-center font-medium hidden sm:block"></div>

    <!-- Kanan: Profil pengguna + tombol logout -->
    <div class="flex items-center space-x-4">
        <!-- Sapaan -->
        <span class="hidden md:inline text-sm text-blue-100">
            Halo, <span class="font-semibold text-white"><?php echo htmlspecialchars($user_nama); ?></span>
        </span>

        <!-- Avatar -->
        <div class="w-10 h-10 rounded-full bg-blue-500 flex items-center justify-center text-white font-bold text-sm shadow-inner">
            <?php echo strtoupper(substr($user_nama, 0, 1)); // Inisial nama ?>
        </div>

        <!-- Tombol Logout -->
        <a href="../../logout.php" 
           class="flex items-center space-x-2 bg-red-500 hover:bg-red-600 text-white px-3 py-2 rounded-lg text-sm font-medium transition duration-200 shadow-md">
            <i class="fas fa-sign-out-alt"></i>
            <span class="hidden sm:inline">Logout</span>
        </a>
    </div>
</header>

<!-- Script Realtime Clock -->
<script>
function updateRealtimeClock() {
    const clockElement = document.getElementById('realtimeClock');
    if (!clockElement) return;

    const now = new Date();
    const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

    const dayName = days[now.getDay()];
    const date = now.getDate();
    const monthName = months[now.getMonth()];
    const year = now.getFullYear();

    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    const seconds = String(now.getSeconds()).padStart(2, '0');

    // Format tampilan
    clockElement.innerHTML = `
        <div class="flex flex-col sm:flex-row sm:items-center sm:space-x-2">
            <span class="font-semibold">${dayName}, ${date} ${monthName} ${year}</span>
            <span class="hidden sm:inline">|</span>
            <span class="text-base font-semibold">${hours}:${minutes}:${seconds}</span>
        </div>`;
}

// Jalankan terus
setInterval(updateRealtimeClock, 1000);
updateRealtimeClock();
</script>
