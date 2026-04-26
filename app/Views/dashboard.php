<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard SmartPresence</title>
    <link rel="stylesheet" href="<?= base_url('app-theme.css') ?>">
</head>
<body class="app-page">
    <header class="app-topbar">
        <div class="app-topbar-inner">
            <div class="brand-stack">
                <span class="brand-kicker">SmartPresence</span>
                <h1 class="brand-title">Dashboard <?= esc(strtoupper($role)) ?></h1>
            </div>
            <div class="topbar-meta">
                <span class="user-pill"><?= esc($nama ?: session()->get('username')) ?></span>
                <a class="btn btn-ghost" href="<?= base_url('logout') ?>">Logout</a>
            </div>
        </div>
    </header>

    <main class="app-shell">
        <?php if (session()->getFlashdata('error')): ?>
            <div class="flash error"><?= esc(session()->getFlashdata('error')) ?></div>
        <?php endif; ?>

        <?php if (session()->getFlashdata('success')): ?>
            <div class="flash success"><?= esc(session()->getFlashdata('success')) ?></div>
        <?php endif; ?>

        <?php if ($role === 'admin'): ?>
            <div class="nav-pills">
                <a class="primary" href="<?= base_url('admin/akun') ?>">Kelola Akun Admin</a>
                <a href="<?= base_url('guru') ?>">Kelola Guru</a>
                <a href="<?= base_url('siswa/data') ?>">Kelola Siswa</a>
                <a href="<?= base_url('admin/registrasi') ?>">Registrasi Wajah & RFID</a>
                <a href="<?= base_url('jadwal') ?>">Kelola Jadwal</a>
                <a class="secondary" href="<?= base_url('presensi/riwayat') ?>">Laporan Presensi</a>
            </div>

            <section class="stat-grid">
                <article class="stat-card">
                    <div class="value"><?= esc((string) ($stats['admin'] ?? 0)) ?></div>
                    <div class="label">Total Akun Admin</div>
                </article>
                <article class="stat-card">
                    <div class="value"><?= esc((string) ($stats['guru'] ?? 0)) ?></div>
                    <div class="label">Total Guru</div>
                </article>
                <article class="stat-card">
                    <div class="value"><?= esc((string) ($stats['siswa'] ?? 0)) ?></div>
                    <div class="label">Total Siswa</div>
                </article>
                <article class="stat-card">
                    <div class="value"><?= esc((string) ($stats['presensi_hari_ini'] ?? 0)) ?></div>
                    <div class="label">Presensi Hari Ini</div>
                </article>
            </section>

            <section class="stat-grid">
                <article class="stat-card">
                    <div class="value"><?= esc((string) ($deviceSummary['online'] ?? 0)) ?></div>
                    <div class="label">Alat Online</div>
                </article>
                <article class="stat-card">
                    <div class="value"><?= esc((string) ($deviceSummary['offline'] ?? 0)) ?></div>
                    <div class="label">Alat Offline</div>
                </article>
                <article class="stat-card">
                    <div class="value"><?= esc((string) ($deviceSummary['window_sec'] ?? 45)) ?>s</div>
                    <div class="label">Batas Online</div>
                </article>
                <article class="stat-card">
                    <div class="value"><?= esc((string) ($deviceSummary['total'] ?? 0)) ?></div>
                    <div class="label">Total Alat Terdaftar</div>
                </article>
            </section>

            <section class="panel">
                <h3>Status Koneksi Alat IoT</h3>
                <p class="helper">Status online dihitung dari heartbeat alat terhadap server dalam rentang waktu batas online.</p>
                <div class="table-wrap" style="margin-top: 10px;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Perangkat</th>
                                <th>Status</th>
                                <th>Mode</th>
                                <th>Last Seen</th>
                                <th>IP</th>
                                <th>Catatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (! empty($deviceSummary['rows'])): ?>
                                <?php foreach ($deviceSummary['rows'] as $row): ?>
                                    <tr>
                                        <td>
                                            <strong><?= esc($row['device_name'] !== '' ? $row['device_name'] : $row['device_code']) ?></strong><br>
                                            <span class="helper"><?= esc($row['device_code']) ?></span>
                                        </td>
                                        <td>
                                            <span class="status-chip <?= $row['is_online'] ? 'success' : 'danger' ?>">
                                                <?= $row['is_online'] ? 'Tersambung' : 'Terputus' ?>
                                            </span>
                                        </td>
                                        <td><?= esc(strtoupper((string) $row['status_mode'])) ?></td>
                                        <td><?= esc((string) $row['last_seen_human']) ?></td>
                                        <td><?= esc((string) ($row['last_ip'] !== '' ? $row['last_ip'] : '-')) ?></td>
                                        <td><?= esc((string) ($row['last_message'] !== '' ? $row['last_message'] : '-')) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6">Belum ada alat IoT yang mengirim heartbeat ke sistem.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="panel">
                <h3>Hak Akses Admin</h3>
                <p>Admin mengelola akun, data guru, data siswa, registrasi wajah, RFID, serta jadwal mengajar.</p>
            </section>
        <?php endif; ?>

        <?php if ($role === 'guru'): ?>
            <div class="nav-pills">
                <a class="primary" href="<?= base_url('presensi') ?>">Presensi Kelas Aktif</a>
                <?php if ((int) ($stats['is_wali_kelas'] ?? 0) === 1): ?>
                    <a class="secondary" href="<?= base_url('presensi/riwayat') ?>">Laporan Presensi</a>
                <?php else: ?>
                    <span class="disabled">Laporan Khusus Wali Kelas</span>
                <?php endif; ?>
            </div>

            <section class="stat-grid">
                <article class="stat-card">
                    <div class="value"><?= esc((string) ($stats['jadwal_hari_ini'] ?? 0)) ?></div>
                    <div class="label">Jadwal Hari Ini</div>
                </article>
                <article class="stat-card">
                    <div class="value"><?= esc((string) ($stats['presensi_hari_ini'] ?? 0)) ?></div>
                    <div class="label">Presensi Dicatat</div>
                </article>
                <article class="stat-card">
                    <div class="value"><?= (int) ($stats['is_wali_kelas'] ?? 0) === 1 ? 'Ya' : 'Tidak' ?></div>
                    <div class="label">Status Wali Kelas</div>
                </article>
                <article class="stat-card">
                    <div class="value"><?= esc((string) ($stats['kelas_wali'] ?? '-')) ?></div>
                    <div class="label">Kelas Wali</div>
                </article>
            </section>

            <section class="panel">
                <h3>Hak Akses Guru</h3>
                <p>Guru hanya dapat mengisi presensi pada kelas aktif saat jam mengajar berjalan. Laporan dan cetak dibatasi untuk guru wali kelas.</p>
            </section>
        <?php endif; ?>
    </main>
</body>
</html>
