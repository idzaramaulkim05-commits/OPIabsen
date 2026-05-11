<?php
$title = (string) ($title ?? 'Dashboard');
$activeNav = (string) ($activeNav ?? '');
$role = (string) session()->get('role');
$userName = (string) (session()->get('nama') ?: session()->get('username') ?: 'Pengguna');

$navItems = [];
if ($role === 'admin') {
    $navItems = [
        ['key' => 'dashboard', 'label' => 'Dashboard', 'url' => base_url('dashboard')],
        ['key' => 'akun', 'label' => 'Akun', 'url' => base_url('admin/akun')],
        ['key' => 'guru', 'label' => 'Guru', 'url' => base_url('guru')],
        ['key' => 'siswa', 'label' => 'Siswa', 'url' => base_url('siswa/data')],
        ['key' => 'registrasi', 'label' => 'Registrasi', 'url' => base_url('admin/registrasi')],
        ['key' => 'jadwal', 'label' => 'Jadwal', 'url' => base_url('jadwal')],
        ['key' => 'kelas', 'label' => 'Kelas', 'url' => base_url('master-data/kelas')],
        ['key' => 'presensi', 'label' => 'Presensi', 'url' => base_url('presensi')],
        ['key' => 'laporan', 'label' => 'Laporan', 'url' => base_url('presensi/riwayat')],
    ];
} elseif ($role === 'guru') {
    $navItems = [
        ['key' => 'dashboard', 'label' => 'Dashboard', 'url' => base_url('dashboard')],
        ['key' => 'presensi', 'label' => 'Presensi', 'url' => base_url('presensi')],
        ['key' => 'laporan', 'label' => 'Laporan', 'url' => base_url('presensi/riwayat')],
    ];
}
?>
<header class="app-topbar">
    <div class="app-topbar-inner">
        <a class="brand-stack" href="<?= base_url('dashboard') ?>" aria-label="SmartPresence dashboard">
            <span class="brand-kicker">SmartPresence</span>
            <span class="brand-title"><?= esc($title) ?></span>
        </a>

        <?php if ($navItems !== []): ?>
            <nav class="topbar-nav" aria-label="Navigasi utama">
                <?php foreach ($navItems as $item): ?>
                    <?php $isActive = $activeNav === $item['key']; ?>
                    <a
                        class="<?= $isActive ? 'is-active' : '' ?>"
                        href="<?= esc($item['url']) ?>"
                        <?= $isActive ? 'aria-current="page"' : '' ?>
                    >
                        <?= esc($item['label']) ?>
                    </a>
                <?php endforeach; ?>
            </nav>
        <?php endif; ?>

        <div class="topbar-meta">
            <span class="user-pill"><?= esc($userName) ?></span>
            <a class="btn btn-ghost" href="<?= base_url('logout') ?>">Logout</a>
        </div>
    </div>
</header>
