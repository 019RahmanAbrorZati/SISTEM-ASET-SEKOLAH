<?php
/**
 * Layout header + sidebar. Set $pageTitle dan $currentPage sebelum include.
 */
$admin = current_admin();
$currentPage = $currentPage ?? '';
$searchPlaceholder = $searchPlaceholder ?? 'Cari aset, nama peminjam...';
$searchValue = trim($_GET['q'] ?? '');
$jumlahTerlambat = isset($pdo) ? jumlah_peminjaman_terlambat($pdo) : 0;

$searchActionByPage = [
    'dasbor'      => url('pages/barang/index.php'),
    'barang'      => url('pages/barang/index.php'),
    'peminjaman'  => url('pages/peminjaman/index.php'),
    'kerusakan'   => url('pages/kerusakan/index.php'),
    'master'      => url('pages/master/index.php'),
    'laporan'     => url('pages/barang/index.php'),
];
$searchAction = $searchActionByPage[$currentPage] ?? url('pages/barang/index.php');
$searchTab = $_GET['tab'] ?? 'kategori';

$nav = [
    'dasbor' => [
        'label' => 'Dasbor',
        'href'  => url('index.php'),
        'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h3.75A2.25 2.25 0 0112 6v3.75a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 9.75V6zM3.75 17.25A2.25 2.25 0 016 15h3.75a2.25 2.25 0 012.25 2.25V21A2.25 2.25 0 019.75 23.25H6A2.25 2.25 0 013.75 21v-3.75zM14.25 6A2.25 2.25 0 0116.5 3.75H20.25A2.25 2.25 0 0122.5 6v3.75a2.25 2.25 0 01-2.25 2.25H16.5a2.25 2.25 0 01-2.25-2.25V6zM14.25 17.25A2.25 2.25 0 0116.5 15h3.75a2.25 2.25 0 012.25 2.25V21a2.25 2.25 0 01-2.25 2.25H16.5a2.25 2.25 0 01-2.25-2.25v-3.75z"/>',
    ],
    'barang' => [
        'label' => 'Data Aset',
        'href'  => url('pages/barang/index.php'),
        'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/>',
    ],
    'peminjaman' => [
        'label' => 'Peminjaman',
        'href'  => url('pages/peminjaman/index.php'),
        'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M8.25 9V5.25A2.25 2.25 0 0110.5 3h6a2.25 2.25 0 012.25 2.25v13.5A2.25 2.25 0 0116.5 21h-6a2.25 2.25 0 01-2.25-2.25V15m-3 0l-3-3m0 0l3-3m-3 3H15"/>',
    ],
    'kerusakan' => [
        'label' => 'Berita Acara',
        'href'  => url('pages/kerusakan/index.php'),
        'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>',
    ],
    'laporan' => [
        'label' => 'Laporan',
        'href'  => url('pages/laporan/index.php'),
        'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/>',
    ],
    'master' => [
        'label' => 'Master Data',
        'href'  => url('pages/master/index.php'),
        'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.43.992a6.759 6.759 0 010 .255c-.008.378.137.75.43.99l1.005.828c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>',
    ],
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($pageTitle ?? 'Dasbor'); ?> — Sistem Aset SDN 1</title>
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
    <style>
        html:not(.dark) .bg-brand,
        html:not(.dark) .hover\:bg-brand-dark:hover {
            background-color: #0B1F4A !important;
            color: #ffffff !important;
        }
        html:not(.dark) .text-brand {
            color: #0B1F4A !important;
        }
        html.dark aside a.nav-link.nav-link-active,
        html.dark aside a.nav-link.nav-link-active span,
        html.dark aside a.nav-link.nav-link-active svg {
            color: #C9A227 !important;
            -webkit-text-fill-color: #C9A227 !important;
            stroke: currentColor;
        }
        html.dark aside a.nav-link:hover,
        html.dark aside a.nav-link.is-going {
            color: #C9A227 !important;
        }
    </style>
</head>
<body class="min-h-screen font-sans text-gray-800">
    <div class="page-sweep" aria-hidden="true"></div>
    <div class="flex min-h-screen">
        <aside class="app-sidebar w-64 shrink-0 flex flex-col">
            <div class="glass-logo mx-3 mt-3 mb-1 px-3 py-3 flex items-center gap-3">
                <div class="glass-logo-mark shrink-0">
                    <img src="<?php echo e(url('assets/img/logo.svg')); ?>" alt="Logo SDN 1" class="w-11 h-11 object-contain">
                </div>
                <div class="min-w-0">
                    <p class="text-xs font-bold text-gold leading-tight">SDN 1</p>
                    <p class="text-[11px] text-gray-500 leading-tight">Sistem Aset Sekolah</p>
                </div>
            </div>

            <nav class="app-nav flex-1 px-3 py-4">
                <div class="app-nav-list">
                    <span class="nav-glass" id="navGlass" aria-hidden="true"></span>
                    <?php foreach ($nav as $key => $item): ?>
                        <?php $active = $currentPage === $key; ?>
                        <a href="<?php echo e($item['href']); ?>"
                           class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium <?php echo $active ? 'nav-link-active' : 'text-gray-600'; ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
                                <?php echo $item['icon']; ?>
                            </svg>
                            <span class="flex-1"><?php echo e($item['label']); ?></span>
                            <?php if ($key === 'peminjaman' && $jumlahTerlambat > 0): ?>
                                <span class="min-w-[1.25rem] h-5 px-1.5 rounded-full bg-red-500 text-white text-[10px] font-bold inline-flex items-center justify-center"><?php echo $jumlahTerlambat; ?></span>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </nav>

            <div class="px-3 py-4 border-t border-gray-100">
                <a href="<?php echo e(url('logout.php')); ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-50">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6A2.25 2.25 0 005.25 5.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9"/>
                    </svg>
                    Keluar
                </a>
            </div>
        </aside>

        <div class="flex-1 min-w-0 flex flex-col">
            <header class="app-topbar h-16 bg-white border-b border-gray-200 flex items-center justify-between px-6 gap-4">
                <form method="get" action="<?php echo e($searchAction); ?>" class="relative w-full max-w-md">
                    <?php if ($currentPage === 'master'): ?>
                        <input type="hidden" name="tab" value="<?php echo e($searchTab); ?>">
                    <?php endif; ?>
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400 pointer-events-none">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10.5a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z"/>
                        </svg>
                    </span>
                    <input type="search" name="q" value="<?php echo e($searchValue); ?>"
                           placeholder="<?php echo e($searchPlaceholder); ?>"
                           class="w-full rounded-lg border border-gray-200 bg-[#F8FAFC] py-2 pl-9 pr-3 text-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand">
                </form>
                <div class="flex items-center gap-3 shrink-0">
                    <button type="button" data-theme-toggle
                            class="theme-toggle w-9 h-9 rounded-lg border border-gray-200 flex items-center justify-center text-gray-600 hover:bg-gray-50"
                            title="Mode malam" aria-label="Ubah ke mode malam">
                        <svg data-icon="moon" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/>
                        </svg>
                        <svg data-icon="sun" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25M12 18.75V21M4.219 4.219l1.591 1.591M18.19 18.19l1.59 1.591M3 12h2.25M18.75 12H21M4.219 19.781l1.591-1.591M18.19 5.81l1.59-1.591M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z"/>
                        </svg>
                    </button>
                    <div class="w-9 h-9 rounded-full bg-brand text-white flex items-center justify-center text-sm font-semibold">
                        <?php echo e(strtoupper(substr($admin['nama'] ?: 'A', 0, 1))); ?>
                    </div>
                    <span class="text-sm font-medium text-gray-700"><?php echo e($admin['nama'] ?: 'Admin'); ?></span>
                </div>
            </header>

            <main class="flex-1 p-6 page-content">
