<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Presensi</title>
    <link rel="stylesheet" href="<?= base_url('app-theme.css') ?>">
</head>
<body class="app-page">
    <?php
    $kelasOptions = is_array($kelasOptions ?? null) ? $kelasOptions : [];
    $guruTanpaKelas = (bool) ($guruTanpaKelas ?? false);
    $scopeInfo = trim((string) ($scopeInfo ?? ''));
    ?>
    <header class="app-topbar">
        <div class="app-topbar-inner">
            <div class="brand-stack">
                <span class="brand-kicker">SmartPresence</span>
                <h1 class="brand-title">Laporan Presensi</h1>
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
            <?php if ($role === 'admin'): ?>
                <a href="<?= base_url('siswa/data') ?>">Data Siswa</a>
                <a href="<?= base_url('guru') ?>">Data Guru</a>
                <a href="<?= base_url('master-data/kelas') ?>">Master Data Kelas</a>
            <?php else: ?>
                <a href="<?= base_url('presensi') ?>">Presensi Aktif</a>
            <?php endif; ?>
        </div>

        <?php if ($scopeInfo !== ''): ?>
            <section class="panel">
                <p><?= esc($scopeInfo) ?></p>
            </section>
        <?php endif; ?>

        <section class="panel">
            <form class="filter-grid" action="<?= base_url('presensi/riwayat') ?>" method="get">
                <div class="field">
                    <label for="mulai">Tanggal Mulai</label>
                    <input id="mulai" type="date" name="mulai" value="<?= esc($mulai) ?>">
                </div>

                <div class="field">
                    <label for="akhir">Tanggal Akhir</label>
                    <input id="akhir" type="date" name="akhir" value="<?= esc($akhir) ?>">
                </div>

                <div class="field">
                    <label for="kelas">Kelas</label>
                    <select id="kelas" name="kelas" <?= $guruTanpaKelas ? 'disabled' : '' ?>>
                        <option value="">
                            <?= $role === 'guru' ? 'Semua kelas yang diampu' : 'Semua kelas' ?>
                        </option>
                        <?php foreach ($kelasOptions as $kelas): ?>
                            <option value="<?= esc((string) $kelas) ?>" <?= $kelasFilter === (string) $kelas ? 'selected' : '' ?>>
                                <?= esc((string) $kelas) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-actions">
                    <button class="btn btn-primary" type="submit">Tampilkan</button>
                    <a class="btn btn-secondary" href="<?= base_url('presensi/cetak?mulai=' . urlencode($mulai) . '&akhir=' . urlencode($akhir) . '&kelas=' . urlencode($kelasFilter)) ?>" target="_blank">Cetak Laporan</a>
                </div>
            </form>
        </section>

        <?php if ($guruTanpaKelas): ?>
            <section class="panel">
                <p>Belum ada kelas yang ditetapkan untuk akun guru ini. Silakan minta admin menambahkan jadwal mengajar terlebih dahulu.</p>
            </section>
        <?php endif; ?>

        <section class="panel">
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Tanggal</th>
                            <th>Kelas</th>
                            <th>Siswa</th>
                            <th>No Induk</th>
                            <th>Status</th>
                            <th>Jam</th>
                            <th>Guru</th>
                            <th>Catatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (! empty($rows)): ?>
                            <?php $no = 1; ?>
                            <?php foreach ($rows as $row): ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><?= esc($row['tanggal']) ?></td>
                                    <td><?= esc($row['kelas']) ?></td>
                                    <td><?= esc($row['nama_siswa']) ?></td>
                                    <td><?= esc($row['no_induk']) ?></td>
                                    <td><?= esc(ucfirst($row['status'])) ?></td>
                                    <td><?= esc($row['jam']) ?></td>
                                    <td><?= esc($row['nama_guru']) ?></td>
                                    <td><?= esc($row['catatan'] ?? '-') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9">Data presensi tidak ditemukan pada filter ini.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>
