<?= view('partials/app_start', [
    'title' => 'Dashboard ' . strtoupper((string) ($role ?? session()->get('role') ?? '')),
    'activeNav' => 'dashboard',
]) ?>

<?php if (($role ?? '') === 'admin'): ?>
    <section class="stat-grid" aria-label="Ringkasan data utama">
        <article class="stat-card">
            <div class="value"><?= esc((string) ($stats['admin'] ?? 0)) ?></div>
            <div class="label">Akun Admin</div>
        </article>
        <article class="stat-card">
            <div class="value"><?= esc((string) ($stats['guru'] ?? 0)) ?></div>
            <div class="label">Guru</div>
        </article>
        <article class="stat-card">
            <div class="value"><?= esc((string) ($stats['siswa'] ?? 0)) ?></div>
            <div class="label">Siswa</div>
        </article>
        <article class="stat-card">
            <div class="value"><?= esc((string) ($stats['presensi_hari_ini'] ?? 0)) ?></div>
            <div class="label">Presensi Hari Ini</div>
        </article>
    </section>

    <section class="stat-grid" aria-label="Ringkasan alat IoT">
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
            <div class="label">Alat Terdaftar</div>
        </article>
    </section>

    <section class="panel">
        <h3>Status Koneksi Alat IoT</h3>
        <p class="page-note">Status online dihitung dari heartbeat alat terhadap server dalam rentang batas online.</p>
        <div class="table-wrap with-space">
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

    <section class="panel compact">
        <h3>Hak Akses Admin</h3>
        <p>Admin mengelola akun, data guru, data siswa, registrasi wajah, RFID, jadwal, master kelas, dan laporan presensi.</p>
        <div class="btn-group">
            <a class="btn btn-primary" href="<?= base_url('presensi/manual') ?>">Absen Manual</a>
            <a class="btn btn-muted" href="<?= base_url('presensi/riwayat') ?>">Lihat Laporan</a>
        </div>
    </section>
<?php endif; ?>

<?php if (($role ?? '') === 'guru'): ?>
    <section class="stat-grid" aria-label="Ringkasan guru">
        <article class="stat-card">
            <div class="value"><?= esc((string) ($stats['jadwal_hari_ini'] ?? 0)) ?></div>
            <div class="label">Jadwal Hari Ini</div>
        </article>
        <article class="stat-card">
            <div class="value"><?= esc((string) ($stats['presensi_hari_ini'] ?? 0)) ?></div>
            <div class="label">Presensi Dicatat</div>
        </article>
        <article class="stat-card">
            <div class="value"><?= esc((string) ($stats['kelas_diampu'] ?? 0)) ?></div>
            <div class="label">Kelas Diampu</div>
        </article>
        <article class="stat-card">
            <div class="value"><?= esc((string) (($stats['kelas_wali'] ?? '') !== '' ? $stats['kelas_wali'] : '-')) ?></div>
            <div class="label">Kelas Wali</div>
        </article>
    </section>

    <section class="panel">
        <h3>Hak Akses Guru</h3>
        <p>Guru dapat memantau presensi dan melihat riwayat sesuai akses kelas yang ditetapkan oleh admin.</p>
        <div class="btn-group">
            <a class="btn btn-primary" href="<?= base_url('presensi') ?>">Buka Presensi</a>
            <a class="btn btn-muted" href="<?= base_url('presensi/riwayat') ?>">Lihat Laporan</a>
        </div>
    </section>
<?php endif; ?>

<?= view('partials/app_end') ?>
