<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Laporan Presensi</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 18px; color: #111827; }
        h2, p { margin: 0 0 8px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #111; padding: 6px; font-size: 12px; }
        th { background: #f3f4f6; }
        @media print {
            .noprint { display: none; }
            body { margin: 0; }
        }
    </style>
</head>
<body>
    <div class="noprint" style="margin-bottom:10px;">
        <button onclick="window.print()">Print</button>
    </div>

    <h2>Laporan Presensi</h2>
    <p>Periode: <?= esc($mulai) ?> s.d. <?= esc($akhir) ?></p>
    <p>Kelas: <?= esc($kelasFilter !== '' ? $kelasFilter : 'Semua') ?></p>

    <table>
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
                <tr><td colspan="9">Tidak ada data pada periode ini.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <script>
        window.onload = () => {
            window.print();
        };
    </script>
</body>
</html>
