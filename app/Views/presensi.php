<?php
$jadwalHariIni = is_array($jadwalHariIni ?? null) ? $jadwalHariIni : [];
$presensiHariIni = is_array($presensiHariIni ?? null) ? $presensiHariIni : [];
$role = (string) session()->get('role');
?>
<?= view('partials/app_start', [
    'title' => 'Presensi Hari Ini',
    'activeNav' => 'presensi',
]) ?>

<div class="page-toolbar">
    <div class="page-toolbar-title">
        <h2>Presensi Hari Ini</h2>
        <p><?= esc($hariIni ?? '-') ?>, <?= esc($jamSekarang ?? '-') ?></p>
    </div>
    <div class="btn-group">
        <?php if ($role === 'admin'): ?>
            <a class="btn btn-primary" href="<?= base_url('presensi/manual') ?>">Absen Manual</a>
        <?php endif; ?>
        <a class="btn btn-muted" href="<?= base_url('presensi/riwayat') ?>">Laporan Presensi</a>
    </div>
</div>

<section class="panel">
    <h3>Jadwal Aktif Hari Ini</h3>
    <?php if ($jadwalHariIni !== []): ?>
        <div class="schedule-list">
            <?php foreach ($jadwalHariIni as $jadwal): ?>
                <?php $shifts = is_array($jadwal['shifts'] ?? null) ? $jadwal['shifts'] : []; ?>
                <?php foreach ($shifts as $shift): ?>
                    <?php $isActive = (bool) ($shift['is_active'] ?? false); ?>
                    <article class="schedule-card">
                        <h4><?= esc((string) ($shift['nama'] ?? 'Jadwal')) ?></h4>
                        <dl>
                            <dt>Masuk</dt>
                            <dd><?= esc((string) ($shift['masuk_awal'] ?? '-')) ?> - <?= esc((string) ($shift['masuk_akhir'] ?? '-')) ?></dd>
                            <dt>Pulang</dt>
                            <dd><?= esc((string) ($shift['pulang_awal'] ?? '-')) ?> - <?= esc((string) ($shift['pulang_akhir'] ?? '-')) ?></dd>
                            <dt>Status</dt>
                            <dd>
                                <span class="status-chip <?= $isActive ? 'success' : 'warning' ?>">
                                    <?= $isActive ? 'Aktif' : 'Terjadwal' ?>
                                </span>
                            </dd>
                        </dl>
                    </article>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <p>Belum ada jadwal masuk dan keluar untuk hari ini.</p>
        </div>
    <?php endif; ?>
</section>

<section class="panel">
    <h3>Presensi Tercatat Hari Ini</h3>
    <p class="page-note">Data presensi diambil dari pencatatan RFID/wajah alat IoT dan ditampilkan sesuai data terbaru dari server.</p>
    <div class="table-wrap with-space">
        <table class="data-table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Tanggal</th>
                    <th>Siswa</th>
                    <th>No Induk</th>
                    <th>Kelas</th>
                    <th>Status</th>
                    <th>Jam</th>
                    <th>Jadwal/Waktu</th>
                    <th>Metode</th>
                    <th>Catatan</th>
                    <?php if ($role === 'admin'): ?><th>Aksi</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if ($presensiHariIni !== []): ?>
                    <?php $no = 1; ?>
                    <?php foreach ($presensiHariIni as $row): ?>
                        <?php
                        $status = strtolower((string) ($row['status'] ?? ''));
                        $statusClass = $status === 'alpa' ? 'danger' : (in_array($status, ['izin', 'sakit'], true) ? 'warning' : 'success');
                        $shiftStatus = (string) ($row['shift_status'] ?? 'in_shift');
                        $shiftClass = $shiftStatus === 'outside_shift' ? 'warning' : ($shiftStatus === 'no_schedule' ? 'danger' : 'success');
                        ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= esc((string) ($row['tanggal'] ?? '-')) ?></td>
                            <td><?= esc((string) ($row['nama_siswa'] ?? '-')) ?></td>
                            <td><?= esc((string) ($row['no_induk'] ?? '-')) ?></td>
                            <td><?= esc((string) ($row['kelas'] ?? '-')) ?></td>
                            <td><span class="status-chip <?= esc($statusClass) ?>"><?= esc(ucfirst((string) ($row['status'] ?? '-'))) ?></span></td>
                            <td><?= esc((string) ($row['jam'] ?? '-')) ?></td>
                            <td>
                                <span class="status-chip <?= esc($shiftClass) ?>"><?= esc((string) ($row['shift_status_label'] ?? '-')) ?></span>
                                <div><?= esc((string) ($row['shift_name'] ?? '-')) ?></div>
                            </td>
                            <td><?= esc((string) ($row['metode'] ?? '-')) ?></td>
                            <td><?= esc((string) (($row['catatan'] ?? '') !== '' ? $row['catatan'] : '-')) ?></td>
                            <?php if ($role === 'admin'): ?>
                                <td>
                                    <div class="actions">
                                        <form action="<?= base_url('presensi/update/' . (int) ($row['id_presensi'] ?? 0)) ?>" method="post" class="inline-form" onsubmit="return promptEditPresensi(this)">
                                            <input type="hidden" name="status" value="<?= esc((string) ($row['status'] ?? 'hadir')) ?>">
                                            <input type="hidden" name="jam" value="<?= esc((string) ($row['jam'] ?? '')) ?>">
                                            <input type="hidden" name="metode" value="<?= esc((string) ($row['metode'] ?? '')) ?>">
                                            <input type="hidden" name="catatan" value="<?= esc((string) ($row['catatan'] ?? '')) ?>">
                                            <button type="submit">Edit</button>
                                        </form>
                                        <form action="<?= base_url('presensi/hapus/' . (int) ($row['id_presensi'] ?? 0)) ?>" method="post" class="inline-form" onsubmit="return confirm('Yakin hapus data presensi ini?')">
                                            <button type="submit" class="link-danger">Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="<?= $role === 'admin' ? '11' : '10' ?>">Belum ada presensi yang tercatat hari ini.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php if ($role === 'admin'): ?>
<script>
function promptEditPresensi(form) {
    const status = window.prompt('Status presensi (hadir/izin/sakit/alpa):', form.status.value || 'hadir');
    if (status === null) return false;
    const jam = window.prompt('Jam presensi (HH:MM):', form.jam.value || '');
    if (jam === null) return false;
    const metode = window.prompt('Metode (rfid_face/rfid_only/face_only):', form.metode.value || '');
    if (metode === null) return false;
    const catatan = window.prompt('Catatan:', form.catatan.value || '');
    if (catatan === null) return false;
    form.status.value = status.trim();
    form.jam.value = jam.trim();
    form.metode.value = metode.trim();
    form.catatan.value = catatan.trim();
    return true;
}
</script>
<?php endif; ?>

<?= view('partials/app_end') ?>
