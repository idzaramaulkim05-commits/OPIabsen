<?php
$kelasOptions = is_array($kelasOptions ?? null) ? $kelasOptions : [];
$shiftStatusOptions = is_array($shiftStatusOptions ?? null) ? $shiftStatusOptions : [];
$shiftStatusFilter = is_array($shiftStatusFilter ?? null) ? $shiftStatusFilter : [];
$guruTanpaKelas = (bool) ($guruTanpaKelas ?? false);
$scopeInfo = trim((string) ($scopeInfo ?? ''));
?>
<?= view('partials/app_start', [
    'title' => 'Laporan Presensi',
    'activeNav' => 'laporan',
]) ?>

<div class="page-toolbar">
    <div class="page-toolbar-title">
        <h2>Laporan Presensi</h2>
        <p>Filter riwayat kehadiran berdasarkan tanggal dan kelas.</p>
    </div>
</div>

<?php if ($scopeInfo !== ''): ?>
    <section class="panel compact">
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
                <option value=""><?= ($role ?? '') === 'guru' ? 'Kelas wali' : 'Semua kelas' ?></option>
                <?php foreach ($kelasOptions as $kelas): ?>
                    <option value="<?= esc((string) $kelas) ?>" <?= $kelasFilter === (string) $kelas ? 'selected' : '' ?>>
                        <?= esc((string) $kelas) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="field full-span">
            <div class="field-label">Jadwal / Waktu</div>
            <div class="checkbox-grid">
                <?php foreach ($shiftStatusOptions as $value => $label): ?>
                    <div class="checkbox-row">
                        <input id="shift_status_<?= esc((string) $value) ?>" type="checkbox" name="shift_status[]" value="<?= esc((string) $value) ?>" <?= in_array((string) $value, $shiftStatusFilter, true) ? 'checked' : '' ?>>
                        <label for="shift_status_<?= esc((string) $value) ?>"><?= esc((string) $label) ?></label>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="filter-actions">
            <button class="btn btn-primary" type="submit">Tampilkan</button>
            <a class="btn btn-secondary" href="<?= base_url('presensi/cetak?' . ($cetakQuery ?? '')) ?>" target="_blank">Cetak Laporan</a>
        </div>
    </form>
</section>

<?php if ($guruTanpaKelas): ?>
    <section class="panel compact">
        <p>Belum ada kelas wali yang ditetapkan untuk akun guru ini. Silakan minta admin menetapkan kelas wali terlebih dahulu.</p>
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
                    <th>Jadwal/Waktu</th>
                    <th>Guru</th>
                    <th>Catatan</th>
                </tr>
            </thead>
            <tbody>
                <?php if (! empty($rows)): ?>
                    <?php $no = 1; ?>
                    <?php foreach ($rows as $row): ?>
                        <?php
                        $status = strtolower((string) ($row['status'] ?? ''));
                        $statusClass = $status === 'alpa' ? 'danger' : (in_array($status, ['izin', 'sakit'], true) ? 'warning' : 'success');
                        $shiftStatus = (string) ($row['shift_status'] ?? 'in_shift');
                        $shiftClass = $shiftStatus === 'outside_shift' ? 'warning' : ($shiftStatus === 'no_schedule' ? 'danger' : 'success');
                        ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= esc($row['tanggal']) ?></td>
                            <td><?= esc($row['kelas']) ?></td>
                            <td><?= esc($row['nama_siswa']) ?></td>
                            <td><?= esc($row['no_induk']) ?></td>
                            <td><span class="status-chip <?= esc($statusClass) ?>"><?= esc(ucfirst((string) $row['status'])) ?></span></td>
                            <td><?= esc($row['jam']) ?></td>
                            <td>
                                <span class="status-chip <?= esc($shiftClass) ?>"><?= esc((string) ($row['shift_status_label'] ?? '-')) ?></span>
                                <div><?= esc((string) ($row['shift_name'] ?? '-')) ?></div>
                            </td>
                            <td><?= esc($row['nama_guru']) ?></td>
                            <td><?= esc($row['catatan'] ?? '-') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="10">Data presensi tidak ditemukan pada filter ini.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?= view('partials/app_end') ?>
