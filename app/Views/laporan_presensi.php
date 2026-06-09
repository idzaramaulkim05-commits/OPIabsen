<?php
$kelasOptions = is_array($kelasOptions ?? null) ? $kelasOptions : [];
$shiftStatusOptions = is_array($shiftStatusOptions ?? null) ? $shiftStatusOptions : [];
$shiftStatusFilter = is_array($shiftStatusFilter ?? null) ? $shiftStatusFilter : [];
$dateColumns = is_array($dateColumns ?? null) ? $dateColumns : [];
$matrixRows = is_array($matrixRows ?? null) ? $matrixRows : [];
$summaryTotals = is_array($summaryTotals ?? null) ? $summaryTotals : [];
$kelasFilter = trim((string) ($kelasFilter ?? ''));
$role = (string) ($role ?? '');
$guruTanpaKelas = (bool) ($guruTanpaKelas ?? false);
$scopeInfo = trim((string) ($scopeInfo ?? ''));
?>
<?= view('partials/app_start', [
    'title' => 'Laporan Presensi',
    'activeNav' => 'laporan',
]) ?>

<div class="page-toolbar">
    <div class="page-toolbar-title report-title-with-logo">
        <img src="<?= base_url('assets/logo-sekolah.png') ?>" alt="Logo sekolah">
        <div>
            <h2>Laporan Presensi</h2>
            <p>Filter rekap kehadiran berdasarkan tanggal dan kelas.</p>
        </div>
    </div>
</div>

<?php if ($scopeInfo !== ''): ?>
    <section class="panel compact">
        <p><?= esc($scopeInfo) ?></p>
    </section>
<?php endif; ?>

<section class="panel report-filter-panel">
    <form class="report-filter" action="<?= base_url('presensi/riwayat') ?>" method="get">
        <div class="report-filter-main">
            <div class="field">
                <label for="mulai">Tanggal Mulai</label>
                <input id="mulai" type="date" name="mulai" value="<?= esc($mulai) ?>">
            </div>

            <div class="field">
                <label for="akhir">Tanggal Akhir</label>
                <input id="akhir" type="date" name="akhir" value="<?= esc($akhir) ?>">
            </div>

            <div class="field report-class-field">
                <label for="kelas">Kelas</label>
                <select id="kelas" name="kelas" <?= $guruTanpaKelas ? 'disabled' : '' ?>>
                    <option value=""><?= $role === 'guru' ? 'Kelas wali' : 'Semua kelas' ?></option>
                    <?php foreach ($kelasOptions as $kelas): ?>
                        <option value="<?= esc((string) $kelas) ?>" <?= $kelasFilter === (string) $kelas ? 'selected' : '' ?>>
                            <?= esc((string) $kelas) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="helper"><?= $role === 'guru' ? 'Data otomatis dibatasi ke kelas wali.' : 'Pilih satu kelas untuk membatasi data.' ?></div>
            </div>

            <div class="report-filter-actions">
                <button class="btn btn-primary" type="submit">Tampilkan</button>
                <a class="btn btn-secondary" href="<?= base_url('presensi/cetak?' . ($cetakQuery ?? '')) ?>" target="_blank">Cetak Laporan</a>
            </div>
        </div>

        <div class="report-shift-filter">
            <div class="field-label">Jadwal / Waktu</div>
            <div class="report-checkbox-grid">
                <?php foreach ($shiftStatusOptions as $value => $label): ?>
                    <div class="checkbox-row">
                        <input id="shift_status_<?= esc((string) $value) ?>" type="checkbox" name="shift_status[]" value="<?= esc((string) $value) ?>" <?= in_array((string) $value, $shiftStatusFilter, true) ? 'checked' : '' ?>>
                        <label for="shift_status_<?= esc((string) $value) ?>"><?= esc((string) $label) ?></label>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </form>
</section>

<?php if ($guruTanpaKelas): ?>
    <section class="panel compact">
        <p>Belum ada kelas wali yang ditetapkan untuk akun guru ini. Silakan minta admin menetapkan kelas wali terlebih dahulu.</p>
    </section>
<?php endif; ?>

<section class="panel">
    <div class="report-summary-bar">
        <div class="report-summary-item">
            <span>Periode</span>
            <strong><?= esc($mulai) ?> s.d. <?= esc($akhir) ?></strong>
        </div>
        <div class="report-summary-item">
            <span>Kelas</span>
            <strong><?= esc($kelasFilter !== '' ? $kelasFilter : (($role ?? '') === 'guru' ? 'Kelas wali' : 'Semua')) ?></strong>
        </div>
        <div class="report-summary-item">
            <span>Jumlah siswa</span>
            <strong><?= esc((string) ($summaryTotals['siswa'] ?? count($matrixRows))) ?></strong>
        </div>
    </div>
    <div class="attendance-legend" aria-label="Legenda status presensi">
        <span><strong>H</strong> Hadir</span>
        <span><strong>I</strong> Izin</span>
        <span><strong>S</strong> Sakit</span>
        <span><strong>A</strong> Alpa</span>
        <span><strong>-</strong> Belum ada data</span>
    </div>

    <div class="table-wrap">
        <table class="data-table attendance-matrix">
            <thead>
                <tr>
                    <th class="matrix-sticky matrix-no" rowspan="2">No</th>
                    <th class="matrix-sticky matrix-nis" rowspan="2">No Induk</th>
                    <th class="matrix-sticky matrix-name" rowspan="2">Nama Siswa</th>
                    <th rowspan="2">Kelas</th>
                    <th class="matrix-date-group" colspan="<?= max(1, count($dateColumns)) ?>">Tanggal</th>
                    <th class="matrix-summary-group" colspan="4">Rekap</th>
                </tr>
                <tr>
                    <?php if ($dateColumns !== []): ?>
                        <?php foreach ($dateColumns as $column): ?>
                            <th class="matrix-date" title="<?= esc((string) ($column['date'] ?? '')) ?>"><?= esc((string) ($column['day'] ?? '')) ?></th>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <th class="matrix-date">-</th>
                    <?php endif; ?>
                    <th class="matrix-total">H</th>
                    <th class="matrix-total">I</th>
                    <th class="matrix-total">S</th>
                    <th class="matrix-total">A</th>
                </tr>
            </thead>
            <tbody>
                <?php if (! empty($matrixRows)): ?>
                    <?php $no = 1; ?>
                    <?php foreach ($matrixRows as $row): ?>
                        <tr>
                            <td class="matrix-sticky matrix-no"><?= $no++ ?></td>
                            <td class="matrix-sticky matrix-nis"><?= esc((string) ($row['no_induk'] ?? '-')) ?></td>
                            <td class="matrix-sticky matrix-name"><?= esc((string) ($row['nama_siswa'] ?? '-')) ?></td>
                            <td><?= esc((string) ($row['kelas'] ?? '-')) ?></td>
                            <?php if ($dateColumns !== []): ?>
                                <?php foreach ($dateColumns as $column): ?>
                                    <?php
                                    $date = (string) ($column['date'] ?? '');
                                    $code = (string) (($row['cells'] ?? [])[$date] ?? '-');
                                    $codeClass = $code !== '-' ? ' status-' . strtolower($code) : ' status-empty';
                                    ?>
                                    <td class="matrix-cell<?= esc($codeClass) ?>"><?= esc($code) ?></td>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <td class="matrix-cell status-empty">-</td>
                            <?php endif; ?>
                            <td class="matrix-total"><?= esc((string) (($row['summary'] ?? [])['H'] ?? 0)) ?></td>
                            <td class="matrix-total"><?= esc((string) (($row['summary'] ?? [])['I'] ?? 0)) ?></td>
                            <td class="matrix-total"><?= esc((string) (($row['summary'] ?? [])['S'] ?? 0)) ?></td>
                            <td class="matrix-total"><?= esc((string) (($row['summary'] ?? [])['A'] ?? 0)) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="<?= 8 + count($dateColumns) ?>">Data siswa atau presensi tidak ditemukan pada filter ini.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
            <?php if (! empty($matrixRows)): ?>
                <tfoot>
                    <tr>
                        <th colspan="4">Total</th>
                        <?php if ($dateColumns !== []): ?>
                            <?php foreach ($dateColumns as $column): ?>
                                <th class="matrix-cell">-</th>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <th class="matrix-cell">-</th>
                        <?php endif; ?>
                        <th class="matrix-total"><?= esc((string) ($summaryTotals['H'] ?? 0)) ?></th>
                        <th class="matrix-total"><?= esc((string) ($summaryTotals['I'] ?? 0)) ?></th>
                        <th class="matrix-total"><?= esc((string) ($summaryTotals['S'] ?? 0)) ?></th>
                        <th class="matrix-total"><?= esc((string) ($summaryTotals['A'] ?? 0)) ?></th>
                    </tr>
                </tfoot>
            <?php endif; ?>
        </table>
    </div>
</section>

<?= view('partials/app_end') ?>
