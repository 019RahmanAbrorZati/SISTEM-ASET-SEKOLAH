<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

// Sudah login → langsung ke dasbor
if (!empty($_SESSION['admin_id'])) {
    header('Location: ' . url('index.php'));
    exit;
}

$error = get_flash('error');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $error = 'Sesi formulir tidak valid. Silakan coba lagi.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($username === '' || $password === '') {
            $error = 'Username dan password wajib diisi.';
        } else {
            $stmt = $pdo->prepare(
                'SELECT ID_Admin, Username, Password, Nama_Lengkap FROM admin WHERE Username = ? LIMIT 1'
            );
            $stmt->execute([$username]);
            $admin = $stmt->fetch();

            if ($admin && password_verify($password, $admin['Password'])) {
                session_regenerate_id(true);
                $_SESSION['admin_id'] = (int) $admin['ID_Admin'];
                $_SESSION['admin_username'] = $admin['Username'];
                $_SESSION['admin_nama'] = $admin['Nama_Lengkap'];
                header('Location: ' . url('index.php'));
                exit;
            }

            $error = 'Username atau password salah.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk — Sistem Aset SDN 1</title>
    <script>
        (function () {
            try {
                var t = localStorage.getItem('sarpras-theme');
                if (!t) t = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
                if (t === 'dark') document.documentElement.classList.add('dark');
            } catch (e) {}
        })();
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        brand: '#0B1F4A',
                        gold: '#C9A227'
                    },
                    fontFamily: { sans: ['Inter', 'system-ui', 'sans-serif'] }
                }
            }
        }
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="<?php echo e(url('assets/css/app.css')); ?>?v=5">
</head>
<body class="min-h-screen bg-[#F3F4F6] font-sans flex items-center justify-center p-4 relative">
    <button type="button" data-theme-toggle
            class="theme-toggle absolute top-4 right-4 w-10 h-10 rounded-lg border border-gray-200 bg-white flex items-center justify-center text-gray-600 hover:bg-gray-50"
            title="Mode malam" aria-label="Ubah ke mode malam">
        <svg data-icon="moon" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/>
        </svg>
        <svg data-icon="sun" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25M12 18.75V21M4.219 4.219l1.591 1.591M18.19 18.19l1.59 1.591M3 12h2.25M18.75 12H21M4.219 19.781l1.591-1.591M18.19 5.81l1.59-1.591M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z"/>
        </svg>
    </button>
    <div class="w-full max-w-md bg-white rounded-2xl shadow-sm border border-gray-100 px-8 py-10">
        <div class="flex flex-col items-center text-center mb-8">
            <img src="<?php echo e(url('assets/img/logo.svg')); ?>" alt="Logo SDN 1" class="w-24 h-24 mb-4 object-contain">
            <h1 class="text-2xl font-bold tracking-wide text-gold">SDN 1</h1>
            <p class="text-sm text-gray-500 mt-1">Sistem Manajemen Aset Sekolah</p>
        </div>

        <?php if ($error): ?>
            <div class="mb-4 rounded-lg bg-red-50 border border-red-100 text-red-700 text-sm px-4 py-3">
                <?php echo e($error); ?>
            </div>
        <?php endif; ?>

        <form method="post" action="" class="space-y-4" autocomplete="off">
            <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">

            <div>
                <label for="username" class="sr-only">Username</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 7.5a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.5 19.5a7.5 7.5 0 0115 0"/>
                        </svg>
                    </span>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        value="<?php echo e($_POST['username'] ?? ''); ?>"
                        placeholder="Masukkan username"
                        class="w-full rounded-lg border border-gray-200 bg-white py-2.5 pl-10 pr-3 text-sm text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand/30 focus:border-brand"
                    >
                </div>
            </div>

            <div>
                <label for="password" class="sr-only">Password</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V8.25a4.5 4.5 0 10-9 0V10.5m-1.5 0h12A1.5 1.5 0 0119.5 12v6A1.5 1.5 0 0118 19.5H6A1.5 1.5 0 014.5 18v-6A1.5 1.5 0 016 10.5z"/>
                        </svg>
                    </span>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Masukkan password"
                        class="w-full rounded-lg border border-gray-200 bg-white py-2.5 pl-10 pr-3 text-sm text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand/30 focus:border-brand"
                    >
                </div>
            </div>

            <button type="submit" class="w-full inline-flex items-center justify-center gap-2 rounded-lg bg-[#C9A227] hover:bg-[#A6851C] text-[#0B1F4A] font-semibold py-2.5 text-sm transition-colors">
                Masuk
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/>
                </svg>
            </button>
        </form>

        <p class="text-center text-xs text-gray-400 mt-6">Gunakan kredensial admin Anda untuk masuk.</p>
    </div>
    <script src="<?php echo e(url('assets/js/theme.js')); ?>"></script>
</body>
</html>
