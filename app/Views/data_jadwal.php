<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Jadwal Mengajar</title>
    <link rel="stylesheet" href="<?= base_url('app-theme.css') ?>">
</head>
<body class="app-page">
    <header class="app-topbar">
        <div class="app-topbar-inner">
            <div class="brand-stack">
                <span class="brand-kicker">SmartPresence</span>
                <h1 class="brand-title">Data Jadwal Mengajar</h1>
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
            <a href="<?= base_url('siswa/data') ?>">Data Siswa</a>
            <a href="<?= base_url('admin/registrasi') ?>">Registrasi Wajah & RFID</a>
            <a class="primary" href="<?= base_url('jadwal/tambah') ?>">Tambah Jadwal</a>
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
                            <th>Guru</th>
                            <th>Kelas</th>
                            <th>Mata Pelajaran</th>
                            <th>Hari</th>
                            <th>Jam</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (! empty($jadwal)): ?>
                            <?php $no = 1; ?>
                            <?php foreach ($jadwal as $row): ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><?= esc($row['nama_guru']) ?></td>
                                    <td><?= esc($row['kelas']) ?></td>
                                    <td><?= esc($row['mata_pelajaran']) ?></td>
                                    <td><?= esc($row['hari']) ?></td>
                                    <td><?= esc($row['jam_mulai']) ?> - <?= esc($row['jam_selesai']) ?></td>
                                    <td>
                                        <div class="actions">
                                            <a href="<?= base_url('jadwal/edit/' . $row['id_jadwal']) ?>">Edit</a>
                                            <a class="danger" href="<?= base_url('jadwal/hapus/' . $row['id_jadwal']) ?>" onclick="return confirm('Yakin hapus jadwal ini?')">Hapus</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7">Belum ada jadwal mengajar.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>
