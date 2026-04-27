<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Akun Admin</title>
    <link rel="stylesheet" href="<?= base_url('app-theme.css') ?>">
</head>
<body class="app-page">
    <header class="app-topbar">
        <div class="app-topbar-inner">
            <div class="brand-stack">
                <span class="brand-kicker">SmartPresence</span>
                <h1 class="brand-title">Kelola Akun Admin</h1>
            </div>
            <div class="topbar-meta">
                <span class="user-pill"><?= esc((string) (session()->get('nama') ?: session()->get('username'))) ?></span>
                <a class="btn btn-ghost" href="<?= base_url('logout') ?>">Logout</a>
            </div>
        </div>
    </header>

    <main class="app-shell">
        <div class="nav-pills">
            <a href="<?= base_url('dashboard') ?>">Dashboard</a>
            <a href="<?= base_url('guru') ?>">Data Guru</a>
            <a href="<?= base_url('siswa/data') ?>">Data Siswa</a>
            <a href="<?= base_url('admin/registrasi') ?>">Registrasi Wajah & RFID</a>
            <a href="<?= base_url('jadwal') ?>">Jadwal</a>
            <a href="<?= base_url('master-data/kelas') ?>">Master Data Kelas</a>
            <a class="primary" href="<?= base_url('admin/akun/tambah') ?>">Tambah Akun Admin</a>
        </div>

        <?php if (session()->getFlashdata('error')): ?>
            <div class="flash error"><?= esc(session()->getFlashdata('error')) ?></div>
        <?php endif; ?>

        <?php if (session()->getFlashdata('success')): ?>
            <div class="flash success"><?= esc(session()->getFlashdata('success')) ?></div>
        <?php endif; ?>

        <section class="panel">
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (! empty($akun)): ?>
                            <?php foreach ($akun as $row): ?>
                                <tr>
                                    <td><?= esc((string) $row['id_admin']) ?></td>
                                    <td><?= esc($row['username']) ?></td>
                                    <td>
                                        <div class="actions">
                                            <a href="<?= base_url('admin/akun/edit/' . $row['id_admin']) ?>">Edit</a>
                                            <a class="danger" href="<?= base_url('admin/akun/hapus/' . $row['id_admin']) ?>" onclick="return confirm('Yakin hapus akun admin ini?')">Hapus</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3">Belum ada akun admin.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>
