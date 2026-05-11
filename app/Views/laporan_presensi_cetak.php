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
            <h2>Laporan Presensi</h2>
            <p>Periode: <?= esc($mulai) ?> s.d. <?= esc($akhir) ?></p>
            <p>Kelas: <?= esc($kelasFilter !== '' ? $kelasFilter : 'Semua') ?></p>
            <?php if (! empty($shiftStatusFilter ?? [])): ?>
                <p>Jadwal/Waktu: <?= esc(implode(', ', array_map(static fn ($status) => (string) (($shiftStatusOptions ?? [])[$status] ?? $status), $shiftStatusFilter))) ?></p>
            <?php endif; ?>
        </section>

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
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><?= esc($row['tanggal']) ?></td>
                                <td><?= esc($row['kelas']) ?></td>
                                <td><?= esc($row['nama_siswa']) ?></td>
                                <td><?= esc($row['no_induk']) ?></td>
                                <td><?= esc(ucfirst((string) $row['status'])) ?></td>
                                <td><?= esc($row['jam']) ?></td>
                                <td><?= esc((string) ($row['shift_status_label'] ?? '-')) ?> - <?= esc((string) ($row['shift_name'] ?? '-')) ?></td>
                                <td><?= esc($row['nama_guru']) ?></td>
                                <td><?= esc($row['catatan'] ?? '-') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="10">Tidak ada data pada periode ini.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

    <script>
        window.addEventListener('load', () => window.print());
    </script>
</body>
</html>
