<?php
$dateColumns = is_array($dateColumns ?? null) ? $dateColumns : [];
$matrixRows = is_array($matrixRows ?? null) ? $matrixRows : [];
$summaryTotals = is_array($summaryTotals ?? null) ? $summaryTotals : [];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Laporan Presensi</title>
    <link rel="stylesheet" href="<?= base_url('app-theme.css') ?>">
</head>
<body class="print-page">
    <main class="print-shell">
        <div class="print-actions noprint">
            <button class="btn btn-primary" type="button" onclick="window.print()">Print</button>
        </div>

        <section class="print-heading">
            <div class="print-brand">SmartPresence</div>
            <h2>Daftar Hadir Peserta Didik</h2>
            <p>Periode: <?= esc($mulai) ?> s.d. <?= esc($akhir) ?></p>
            <p>Kelas: <?= esc($kelasFilter !== '' ? $kelasFilter : 'Semua') ?></p>
            <?php if (! empty($shiftStatusFilter ?? [])): ?>
                <p>Jadwal/Waktu: <?= esc(implode(', ', array_map(static fn ($status) => (string) (($shiftStatusOptions ?? [])[$status] ?? $status), $shiftStatusFilter))) ?></p>
            <?php endif; ?>
            <p>Legenda: H = Hadir, I = Izin, S = Sakit, A = Alpa, - = Belum ada data</p>
        </section>

        <div class="table-wrap">
            <table class="data-table attendance-matrix print-matrix">
                <thead>
                    <tr>
                        <th rowspan="2">No</th>
                        <th rowspan="2">No Induk</th>
                        <th rowspan="2">Nama Siswa</th>
                        <th rowspan="2">Kelas</th>
                        <th colspan="<?= max(1, count($dateColumns)) ?>">Tanggal</th>
                        <th colspan="4">Rekap</th>
                    </tr>
                    <tr>
                        <?php if ($dateColumns !== []): ?>
                            <?php foreach ($dateColumns as $column): ?>
                                <th class="matrix-date" title="<?= esc((string) ($column['date'] ?? '')) ?>"><?= esc((string) ($column['day'] ?? '')) ?></th>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <th class="matrix-date">-</th>
                        <?php endif; ?>
                        <th>H</th>
                        <th>I</th>
                        <th>S</th>
                        <th>A</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (! empty($matrixRows)): ?>
                        <?php $no = 1; ?>
                        <?php foreach ($matrixRows as $row): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><?= esc((string) ($row['no_induk'] ?? '-')) ?></td>
                                <td><?= esc((string) ($row['nama_siswa'] ?? '-')) ?></td>
                                <td><?= esc((string) ($row['kelas'] ?? '-')) ?></td>
                                <?php if ($dateColumns !== []): ?>
                                    <?php foreach ($dateColumns as $column): ?>
                                        <?php
                                        $date = (string) ($column['date'] ?? '');
                                        $code = (string) (($row['cells'] ?? [])[$date] ?? '-');
                                        ?>
                                        <td class="matrix-cell"><?= esc($code) ?></td>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <td class="matrix-cell">-</td>
                                <?php endif; ?>
                                <td class="matrix-total"><?= esc((string) (($row['summary'] ?? [])['H'] ?? 0)) ?></td>
                                <td class="matrix-total"><?= esc((string) (($row['summary'] ?? [])['I'] ?? 0)) ?></td>
                                <td class="matrix-total"><?= esc((string) (($row['summary'] ?? [])['S'] ?? 0)) ?></td>
                                <td class="matrix-total"><?= esc((string) (($row['summary'] ?? [])['A'] ?? 0)) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="<?= 8 + count($dateColumns) ?>">Tidak ada data pada periode ini.</td>
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
    </main>

    <script>
        window.addEventListener('load', () => window.print());
    </script>
</body>
</html>
