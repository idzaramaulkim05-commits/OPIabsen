<?php
$kelasOptions = is_array($kelasOptions ?? null) ? $kelasOptions : [];
$students = is_array($students ?? null) ? $students : [];
$kelasFilter = trim((string) ($kelasFilter ?? ''));
$tanggalHariIni = (string) ($tanggalHariIni ?? date('Y-m-d'));
$hasStudents = false;
foreach ($students as $student) {
    if ((int) ($student['id_siswa'] ?? 0) > 0) {
        $hasStudents = true;
        break;
    }
}
?>
<?= view('partials/app_start', [
    'title' => 'Absen Manual',
    'activeNav' => 'absen_manual',
]) ?>

<div class="page-toolbar">
    <div class="page-toolbar-title">
        <h2>Absen Manual</h2>
        <p>Input status Sakit, Izin, atau Alpa untuk siswa tertentu.</p>
    </div>
</div>

<section class="panel report-filter-panel">
    <form class="report-filter" action="<?= base_url('presensi/manual') ?>" method="get">
        <div class="report-filter-main">
            <div class="field report-class-field">
                <label for="kelas">Kelas</label>
                <select id="kelas" name="kelas">
                    <option value="">Semua kelas</option>
                    <?php foreach ($kelasOptions as $kelas): ?>
                        <option value="<?= esc((string) $kelas) ?>" <?= $kelasFilter === (string) $kelas ? 'selected' : '' ?>>
                            <?= esc((string) $kelas) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="helper">Daftar siswa pada form mengikuti pilihan kelas.</div>
            </div>

            <div class="report-filter-actions">
                <button class="btn btn-primary" type="submit">Tampilkan</button>
                <a class="btn btn-secondary" href="<?= base_url('presensi/manual') ?>">Reset</a>
            </div>
        </div>
    </form>
</section>

<section class="panel report-manual-panel">
    <h3>Input Status Kehadiran</h3>
    <form class="form-grid two" action="<?= base_url('presensi/manual') ?>" method="post">
        <input type="hidden" name="kelas_filter" value="<?= esc($kelasFilter) ?>">

        <div class="field">
            <label for="manual_tanggal">Tanggal</label>
            <input id="manual_tanggal" type="date" name="tanggal" value="<?= esc(old('tanggal', $tanggalHariIni)) ?>" required>
        </div>

        <div class="field">
            <label for="manual_status">Status</label>
            <select id="manual_status" name="status" required>
                <?php foreach (['sakit' => 'Sakit', 'izin' => 'Izin', 'alpa' => 'Alpa'] as $value => $label): ?>
                    <option value="<?= esc($value) ?>" <?= old('status') === $value ? 'selected' : '' ?>>
                        <?= esc($label) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="field">
            <label for="manual_siswa">Siswa</label>
            <select id="manual_siswa" name="id_siswa" required>
                <option value="">Pilih siswa</option>
                <?php foreach ($students as $student): ?>
                    <?php
                    $idSiswa = (int) ($student['id_siswa'] ?? 0);
                    if ($idSiswa <= 0) {
                        continue;
                    }
                    $label = trim((string) ($student['nama_siswa'] ?? '-')) . ' - ' . trim((string) ($student['kelas'] ?? '-'));
                    ?>
                    <option value="<?= $idSiswa ?>" <?= (string) old('id_siswa') === (string) $idSiswa ? 'selected' : '' ?>>
                        <?= esc($label) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <div class="helper"><?= $hasStudents ? 'Pilih siswa sesuai filter kelas.' : 'Tidak ada siswa pada filter kelas ini.' ?></div>
        </div>

        <div class="field">
            <label for="manual_catatan">Catatan</label>
            <input id="manual_catatan" type="text" name="catatan" value="<?= esc(old('catatan')) ?>" placeholder="Opsional">
        </div>

        <div class="btn-group">
            <button class="btn btn-primary" type="submit" <?= $hasStudents ? '' : 'disabled' ?>>Simpan Status</button>
            <a class="btn btn-muted" href="<?= base_url('presensi/riwayat') ?>">Lihat Laporan</a>
        </div>
    </form>
</section>

<?= view('partials/app_end') ?>
