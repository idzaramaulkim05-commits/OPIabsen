<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Siswa</title>
    <link rel="stylesheet" href="<?= base_url('app-theme.css') ?>">
</head>
<body class="app-page">
    <header class="app-topbar">
        <div class="app-topbar-inner">
            <div class="brand-stack">
                <span class="brand-kicker">SmartPresence</span>
                <h1 class="brand-title">Data Siswa</h1>
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
            <a href="<?= base_url('admin/akun') ?>">Akun Admin</a>
            <a href="<?= base_url('guru') ?>">Data Guru</a>
            <a href="<?= base_url('admin/registrasi') ?>">Registrasi Wajah & RFID</a>
            <a href="<?= base_url('jadwal') ?>">Jadwal</a>
            <a href="<?= base_url('master-data/kelas') ?>">Master Data Kelas</a>
            <a class="primary" href="<?= base_url('siswa/tambah') ?>">Tambah Siswa</a>
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
                            <th>No</th>
                            <th>Wajah</th>
                            <th>Nama</th>
                            <th>No Induk</th>
                            <th>Kelas</th>
                            <th>Alamat</th>
                            <th>RFID</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (! empty($siswa)): ?>
                            <?php $no = 1; ?>
                            <?php foreach ($siswa as $row): ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td>
                                        <?php if (! empty($row['foto_wajah'])): ?>
                                            <img class="face-thumb" src="<?= esc($row['foto_wajah']) ?>" alt="Wajah Siswa">
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td><?= esc($row['nama']) ?></td>
                                    <td><?= esc((string) (($row['no_induk'] ?? '') !== '' ? $row['no_induk'] : '-')) ?></td>
                                    <td><?= esc((string) (($row['kelas'] ?? '') !== '' ? $row['kelas'] : '-')) ?></td>
                                    <td><?= esc((string) (($row['alamat'] ?? '') !== '' ? $row['alamat'] : '-')) ?></td>
                                    <td><?= esc($row['id_rfid'] ?? '-') ?></td>
                                    <td>
                                        <div class="actions">
                                            <a href="<?= base_url('siswa/edit/' . $row['id']) ?>">Edit</a>
                                            <a class="danger" href="<?= base_url('siswa/hapus/' . $row['id']) ?>" onclick="return confirm('Yakin hapus data siswa ini?')">Hapus</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8">Belum ada data siswa.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>
