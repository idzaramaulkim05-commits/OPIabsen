<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Presensi Kelas Aktif</title>
    <link rel="stylesheet" href="<?= base_url('app-theme.css') ?>">
</head>
<body class="app-page">
    <header class="app-topbar">
        <div class="app-topbar-inner">
            <div class="brand-stack">
                <span class="brand-kicker">SmartPresence</span>
                <h1 class="brand-title">Presensi Kelas Aktif</h1>
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
            <a class="secondary" href="<?= base_url('presensi/riwayat') ?>">Laporan Presensi</a>
        </div>

        <?php if (session()->getFlashdata('error')): ?>
            <div class="flash error"><?= esc(session()->getFlashdata('error')) ?></div>
        <?php endif; ?>

        <?php if (session()->getFlashdata('success')): ?>
            <div class="flash success"><?= esc(session()->getFlashdata('success')) ?></div>
        <?php endif; ?>

        <section class="panel">
            <h3>Waktu Sistem</h3>
            <p><strong><?= esc($hariIni) ?></strong>, <?= esc($jamSekarang) ?></p>
            <p class="helper">Guru hanya dapat mengisi presensi pada jadwal yang aktif di jam mengajar.</p>
        </section>

        <?php if (! empty($jadwalAktif)): ?>
            <section class="panel">
                <form action="<?= base_url('presensi') ?>" method="get" class="form-grid">
                    <div class="field">
                        <label for="jadwal">Pilih Jadwal Aktif</label>
                        <select id="jadwal" name="jadwal" onchange="this.form.submit()">
                            <?php foreach ($jadwalAktif as $j): ?>
                                <option value="<?= esc((string) $j['id_jadwal']) ?>" <?= ($jadwalDipilih && (int) $jadwalDipilih['id_jadwal'] === (int) $j['id_jadwal']) ? 'selected' : '' ?>>
                                    <?= esc($j['kelas']) ?> - <?= esc($j['mata_pelajaran']) ?> (<?= esc($j['jam_mulai']) ?> - <?= esc($j['jam_selesai']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            </section>

            <?php if ($jadwalDipilih): ?>
                <section class="panel">
                    <div class="table-wrap">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama Siswa</th>
                                    <th>No Induk</th>
                                    <th>Status Saat Ini</th>
                                    <th>Input Presensi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (! empty($siswa)): ?>
                                    <?php $no = 1; ?>
                                    <?php foreach ($siswa as $row): ?>
                                        <?php $p = $presensiMap[$row['id']] ?? null; ?>
                                        <tr>
                                            <td><?= $no++ ?></td>
                                            <td><?= esc($row['nama']) ?></td>
                                            <td><?= esc($row['no_induk']) ?></td>
                                            <td><?= esc($p['status'] ?? '-') ?></td>
                                            <td>
                                                <form action="<?= base_url('presensi/simpan') ?>" method="post" class="inline-controls">
                                                    <input type="hidden" name="id_jadwal" value="<?= esc((string) $jadwalDipilih['id_jadwal']) ?>">
                                                    <input type="hidden" name="id_siswa" value="<?= esc((string) $row['id']) ?>">
                                                    <select name="status" required>
                                                        <?php $statusNow = $p['status'] ?? 'hadir'; ?>
                                                        <option value="hadir" <?= $statusNow === 'hadir' ? 'selected' : '' ?>>Hadir</option>
                                                        <option value="izin" <?= $statusNow === 'izin' ? 'selected' : '' ?>>Izin</option>
                                                        <option value="sakit" <?= $statusNow === 'sakit' ? 'selected' : '' ?>>Sakit</option>
                                                        <option value="alpa" <?= $statusNow === 'alpa' ? 'selected' : '' ?>>Alpa</option>
                                                    </select>
                                                    <input type="text" name="catatan" placeholder="Catatan" value="<?= esc($p['catatan'] ?? '') ?>">
                                                    <button class="btn btn-primary" type="submit">Simpan</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5">Tidak ada siswa pada kelas ini.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            <?php endif; ?>
        <?php else: ?>
            <section class="panel">
                <p>Tidak ada jadwal aktif saat ini. Presensi bisa diisi saat jam mengajar berlangsung.</p>
            </section>
        <?php endif; ?>
    </main>
</body>
</html>
