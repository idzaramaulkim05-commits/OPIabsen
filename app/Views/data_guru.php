<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Guru</title>
    <link rel="stylesheet" href="<?= base_url('app-theme.css') ?>">
</head>
<body class="app-page">
    <header class="app-topbar">
        <div class="app-topbar-inner">
            <div class="brand-stack">
                <span class="brand-kicker">SmartPresence</span>
                <h1 class="brand-title">Data Guru</h1>
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
            <a href="<?= base_url('siswa/data') ?>">Data Siswa</a>
            <a href="<?= base_url('admin/registrasi') ?>">Registrasi Wajah & RFID</a>
            <a href="<?= base_url('jadwal') ?>">Jadwal</a>
            <a href="<?= base_url('master-data/kelas') ?>">Master Data Kelas</a>
            <a class="primary" href="<?= base_url('guru/tambah') ?>">Tambah Guru</a>
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
                            <th>NIP</th>
                            <th>Username</th>
                            <th>RFID</th>
                            <th>Wali Kelas</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (! empty($guru)): ?>
                            <?php $no = 1; ?>
                            <?php foreach ($guru as $row): ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td>
                                        <?php if (! empty($row['foto_wajah'])): ?>
                                            <img class="face-thumb" src="<?= esc($row['foto_wajah']) ?>" alt="Wajah Guru">
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td><?= esc($row['nama']) ?></td>
                                    <td><?= esc($row['nip']) ?></td>
                                    <td><?= esc($row['username']) ?></td>
                                    <td><?= esc($row['id_rfid'] ?? '-') ?></td>
                                    <td>
                                        <?= (int) ($row['is_wali_kelas'] ?? 0) === 1 ? 'Ya (' . esc($row['kelas_wali'] ?? '-') . ')' : 'Tidak' ?>
                                    </td>
                                    <td>
                                        <div class="actions">
                                            <a href="<?= base_url('guru/edit/' . $row['id_guru']) ?>">Edit</a>
                                            <a href="<?= base_url('admin/registrasi?target_type=guru&target_id=' . $row['id_guru']) ?>">Registrasi</a>
                                            <a class="danger" href="<?= base_url('guru/hapus/' . $row['id_guru']) ?>" onclick="return confirm('Yakin hapus data guru ini?')">Hapus</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8">Belum ada data guru.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>
