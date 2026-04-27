<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Master Data Kelas</title>
    <link rel="stylesheet" href="<?= base_url('app-theme.css') ?>">
</head>
<body class="app-page">
    <header class="app-topbar">
        <div class="app-topbar-inner">
            <div class="brand-stack">
                <span class="brand-kicker">SmartPresence</span>
                <h1 class="brand-title">Master Data Kelas</h1>
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
            <a href="<?= base_url('jadwal') ?>">Jadwal</a>
            <a class="primary" href="<?= base_url('master-data/kelas') ?>">Master Data Kelas</a>
        </div>

        <?php if (session()->getFlashdata('error')): ?>
            <div class="flash error"><?= esc(session()->getFlashdata('error')) ?></div>
        <?php endif; ?>

        <?php if (session()->getFlashdata('success')): ?>
            <div class="flash success"><?= esc(session()->getFlashdata('success')) ?></div>
        <?php endif; ?>

        <section class="panel form-card">
            <h3>Tambah Kelas</h3>
            <form action="<?= base_url('master-data/kelas/simpan') ?>" method="post" class="form-grid">
                <?= csrf_field() ?>
                <div class="field">
                    <label for="nama_kelas">Nama Kelas</label>
                    <input
                        id="nama_kelas"
                        type="text"
                        name="nama_kelas"
                        value="<?= esc(old('nama_kelas', '')) ?>"
                        placeholder="Contoh: XI-RPL-1"
                        required
                    >
                </div>
                <div class="btn-group">
                    <button class="btn btn-primary" type="submit">Simpan Kelas</button>
                </div>
            </form>
        </section>

        <section class="panel">
            <h3>Daftar Kelas</h3>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Kelas</th>
                            <th>Dipakai (data terkait)</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (! empty($kelas)): ?>
                            <?php $no = 1; ?>
                            <?php foreach ($kelas as $row): ?>
                                <?php $namaKelas = (string) ($row['nama_kelas'] ?? ''); ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><?= esc($namaKelas) ?></td>
                                    <td><?= esc((string) ((int) ($usageMap[$namaKelas] ?? 0))) ?></td>
                                    <td>
                                        <form action="<?= base_url('master-data/kelas/update/' . (int) $row['id_kelas']) ?>" method="post" class="inline-controls">
                                            <?= csrf_field() ?>
                                            <input
                                                type="text"
                                                name="nama_kelas"
                                                value="<?= esc($namaKelas) ?>"
                                                required
                                                style="min-width: 180px;"
                                            >
                                            <button class="btn btn-secondary" type="submit">Update</button>
                                            <a
                                                class="btn btn-muted"
                                                href="<?= base_url('master-data/kelas/hapus/' . (int) $row['id_kelas']) ?>"
                                                onclick="return confirm('Yakin hapus kelas ini?')"
                                            >Hapus</a>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4">Belum ada data kelas di master data.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>
