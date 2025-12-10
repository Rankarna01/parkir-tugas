<?php
// templates/sidebar.php

$current_page = basename($_SERVER['PHP_SELF']);
$user_role = $_SESSION['role'] ?? 'pekerja';
?>

<!-- Sidebar Wrapper -->
<div x-data="{ open: true }" class="relative">

    <!-- Toggler Button (Mobile) -->
    <button @click="open = !open"
        class="absolute top-4 left-4 z-50 md:hidden bg-blue-600 text-white p-2 rounded-lg shadow-lg focus:outline-none transition hover:bg-blue-700">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Sidebar -->
    <div :class="open ? 'translate-x-0' : '-translate-x-64'"
        class="fixed md:static inset-y-0 left-0 w-64 bg-gradient-to-b from-blue-700 via-blue-800 to-blue-900 text-white flex flex-col shadow-xl transform transition-transform duration-300 ease-in-out z-40 h-screen">

        <!-- Logo -->
        <div class="flex items-center justify-center h-20 border-b border-blue-600">
            <div class="flex items-center space-x-3">
                <div class="bg-blue-500 text-white rounded-lg p-2">
                    <i class="fas fa-parking text-2xl"></i>
                </div>
                <span class="text-2xl font-bold tracking-wide">
                    Parkir<span class="text-blue-300">Sistem</span>
                </span>
            </div>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto scrollbar-thin scrollbar-thumb-blue-600 scrollbar-track-blue-900">

            <?php if ($user_role == 'owner'): ?>
                <a href="dashboard.php"
                    class="flex items-center px-4 py-2.5 rounded-lg font-medium transition duration-200 
                    <?php echo ($current_page == 'dashboard.php') ? 'bg-blue-500 text-white shadow-md' : 'hover:bg-blue-700'; ?>">
                    <i class="fas fa-gauge w-6 text-center"></i>
                    <span class="ml-4">Dashboard</span>
                </a>

                <a href="laporan.php"
                    class="flex items-center px-4 py-2.5 rounded-lg font-medium transition duration-200 
                    <?php echo ($current_page == 'laporan.php') ? 'bg-blue-500 text-white shadow-md' : 'hover:bg-blue-700'; ?>">
                    <i class="fas fa-file-alt w-6 text-center"></i>
                    <span class="ml-4">Laporan</span>
                </a>

                <a href="manajemen_pekerja.php"
                    class="flex items-center px-4 py-2.5 rounded-lg font-medium transition duration-200 
                    <?php echo ($current_page == 'manajemen_pekerja.php') ? 'bg-blue-500 text-white shadow-md' : 'hover:bg-blue-700'; ?>">
                    <i class="fas fa-users w-6 text-center"></i>
                    <span class="ml-4">Manajemen Pekerja</span>
                </a>

                <a href="pengaturan_tarif.php"
                    class="flex items-center px-4 py-2.5 rounded-lg font-medium transition duration-200 
                    <?php echo ($current_page == 'pengaturan_tarif.php') ? 'bg-blue-500 text-white shadow-md' : 'hover:bg-blue-700'; ?>">
                    <i class="fas fa-dollar-sign w-6 text-center"></i>
                    <span class="ml-4">Pengaturan Tarif</span>
                </a>

                <a href="riwayat_kendaraan.php"
                    class="flex items-center px-4 py-2.5 rounded-lg font-medium transition duration-200 
                    <?php echo ($current_page == 'riwayat_kendaraan.php') ? 'bg-blue-500 text-white shadow-md' : 'hover:bg-blue-700'; ?>">
                    <i class="fas fa-rotate-left w-6 text-center"></i>
                    <span class="ml-4">Riwayat Kendaraan</span>
                </a>

            <?php elseif ($user_role == 'pekerja'): ?>
            <a href="pos_parkir.php" class="flex items-center px-4 py-2.5 rounded-lg transition duration-200 <?php echo ($current_page == 'pos_parkir.php') ? 'bg-blue-600' : 'hover:bg-gray-700'; ?>">
                <i class="fas fa-desktop w-6 text-center"></i>
                <span class="ml-4">Pos Parkir</span>
            </a>
            <a href="riwayat_kendaraan.php" class="flex items-center px-4 py-2.5 rounded-lg transition duration-200 <?php echo ($current_page == 'riwayat_kendaraan.php') ? 'bg-blue-600' : 'hover:bg-gray-700'; ?>">
                <i class="fas fa-history w-6 text-center"></i>
                <span class="ml-4">Riwayat Kendaraan</span>
            </a>
            <a href="dashboard.php" class="flex items-center px-4 py-2.5 rounded-lg transition duration-200 <?php echo ($current_page == 'dashboard.php') ? 'bg-blue-600' : 'hover:bg-gray-700'; ?>">
                <i class="fas fa-tachometer-alt w-6 text-center"></i>
                <span class="ml-4">Dashboard</span>
            </a>
        
        <?php endif; ?>
        </nav>

        <!-- Logout -->
        <div class="px-4 py-4 border-t border-blue-600">
            <a href="../../logout.php"
                class="flex items-center px-4 py-2.5 rounded-lg text-red-300 hover:bg-red-600 hover:text-white font-medium transition duration-200">
                <i class="fas fa-sign-out-alt w-6 text-center"></i>
                <span class="ml-4">Logout</span>
            </a>
        </div>
    </div>

    <!-- Overlay (Mobile) -->
    <div x-show="open" @click="open = false" class="fixed inset-0 bg-black bg-opacity-40 z-30 md:hidden"></div>
</div>

<!-- Alpine.js -->
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
