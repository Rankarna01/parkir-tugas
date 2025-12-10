<?php
//=========================================
// LOGIKA PHP (BAGIAN ATAS)
//=========================================
$page_title = "Login Sistem Parkir";
require_once 'core/init.php';

// Variabel untuk menyimpan pesan error
$error = '';

// Cek jika sudah login, lempar ke index.php
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Cek jika ada data POST (form disubmit)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = 'Email dan password tidak boleh kosong!';
    } else {
        // Ambil data user dari database
        $stmt = $db->prepare("SELECT id, nama, email, password, role FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            
            // Verifikasi password
            if (password_verify($password, $user['password'])) {
                // Password benar, buat session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['nama'] = $user['nama'];
                $_SESSION['role'] = $user['role'];
                
                // Redirect ke halaman index (dashboard)
                header('Location: index.php');
                exit;
            } else {
                // Password salah
                $error = 'Email atau password salah.';
            }
        } else {
            // Email tidak ditemukan
            $error = 'Email atau password salah.';
        }
        $stmt->close();
    }
}
$db->close();

//=========================================
// TAMPILAN HTML (BAGIAN BAWAH)
//=========================================
?>

<?php require_once 'templates/header.php'; // Memanggil header (yang berisi CDN) ?>


<!-- Wrapper -->
<div class="min-h-screen flex items-center justify-center bg-gray-50">
  <!-- Card 2 Kolom -->
  <div class="w-full max-w-5xl bg-white rounded-2xl shadow-xl overflow-hidden grid grid-cols-1 lg:grid-cols-2 border border-gray-100">

    <!-- Kolom Kiri: Gambar / Ilustrasi -->
    <div class="relative hidden lg:block">
      <!-- Pakai gradient biru + ikon besar; bisa diganti gambar sendiri kalau punya -->
      <div class="absolute inset-0 bg-gradient-to-br from-blue-700 via-blue-800 to-blue-900"></div>
      <div class="relative h-full w-full flex flex-col items-center justify-center text-white p-10">
        <div class="bg-white/15 backdrop-blur-sm w-20 h-20 rounded-2xl flex items-center justify-center shadow-lg mb-6">
          <i class="fas fa-parking text-4xl"></i>
        </div>
        <h2 class="text-2xl font-bold mb-2">Sistem Manajemen Parkir</h2>
        <p class="text-blue-100 text-center max-w-sm">
          Kelola parkir lebih cepat, aman, dan akurat. Masuk untuk mulai memantau aktivitas harian.
        </p>

        <!-- “Gambar” dekoratif (SVG wave) -->
        <svg class="mt-8 w-4/5" viewBox="0 0 1440 320" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
          <path fill="currentColor" fill-opacity=".15"
            d="M0,224L48,224C96,224,192,224,288,197.3C384,171,480,117,576,96C672,75,768,85,864,90.7C960,96,1056,96,1152,90.7C1248,85,1344,75,1392,69.3L1440,64L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"/>
        </svg>
      </div>
    </div>

    <!-- Kolom Kanan: Form -->
    <div class="p-8 md:p-10">
      <div class="mb-6">
        <div class="flex items-center gap-3">
          <div class="bg-blue-600 text-white w-10 h-10 rounded-xl flex items-center justify-center shadow">
            <i class="fas fa-lock"></i>
          </div>
          <div>
            <h1 class="text-2xl font-bold text-gray-900 leading-tight">Masuk</h1>
            <p class="text-sm text-gray-500 -mt-0.5">Login ke akun Anda untuk melanjutkan</p>
          </div>
        </div>
      </div>

      <form action="login.php" method="POST" class="space-y-5">
        <?php if (!empty($error)): ?>
          <div class="flex items-start gap-3 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl">
            <i class="fas fa-exclamation-triangle mt-0.5"></i>
            <span class="text-sm"><?php echo $error; ?></span>
          </div>
        <?php endif; ?>

        <!-- Email -->
        <div>
          <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
          <div class="relative">
            <i class="fas fa-envelope absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
            <input
              type="email" id="email" name="email" required
              class="w-full pl-10 pr-3 py-2.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
              placeholder="contoh@email.com">
          </div>
        </div>

        <!-- Password -->
        <div>
          <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
          <div class="relative">
            <i class="fas fa-key absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
            <input
              type="password" id="password" name="password" required
              class="w-full pl-10 pr-12 py-2.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
              placeholder="••••••••">
            <button type="button" id="togglePw"
              class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 p-2"
              aria-label="Tampilkan/Sembunyikan password">
              <i class="fas fa-eye"></i>
            </button>
          </div>
        </div>

        <!-- Opsional: remember (front-end only, tidak mempengaruhi backend) -->
        <div class="flex items-center justify-between">
          <label class="inline-flex items-center gap-2 text-sm text-gray-600 select-none">
            <input type="checkbox" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
            Ingat saya
          </label>
          <a href="#" class="text-sm text-blue-600 hover:text-blue-700">Lupa password?</a>
        </div>

        <!-- Submit -->
        <div class="pt-2">
          <button type="submit"
            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 rounded-xl shadow-sm transition active:scale-[.99]">
            Masuk
          </button>
        </div>
      </form>

      <!-- Footer kecil -->
      <p class="mt-6 text-xs text-gray-400">
        © <?php echo date('Y'); ?> Sistem Parkir — versi aman & modern.
      </p>
    </div>
  </div>
</div>

<?php require_once 'templates/footer.php'; // Memanggil footer ?>

<!-- Toggle show/hide password (front-end only) -->
<script>
  (function() {
    const input = document.getElementById('password');
    const btn = document.getElementById('togglePw');
    if (!input || !btn) return;

    btn.addEventListener('click', () => {
      const showing = input.type === 'text';
      input.type = showing ? 'password' : 'text';
      btn.innerHTML = showing ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
    });
  })();
</script>
