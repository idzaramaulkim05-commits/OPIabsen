<?= view('partials/app_start', [
    'title' => 'Jadwal Masuk & Keluar',
    'activeNav' => 'jadwal',
]) ?>

<div class="page-toolbar">
    <div class="page-toolbar-title">
        <h2>Jadwal Masuk & Keluar</h2>
        <p>Atur rentang waktu masuk dan pulang untuk setiap hari.</p>
    </div>
    <a class="btn btn-primary" href="<?= base_url('jadwal/tambah') ?>">Tambah Jadwal</a>
</div>

<section class="panel">
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Hari</th>
                    <th>Total Jadwal</th>
                    <th>Detail Waktu</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (! empty($jadwal)): ?>
                    <?php $no = 1; ?>
                    <?php foreach ($jadwal as $row): ?>
                        <?php
                        $rawShifts = $row['shifts'] ?? [];
                        if (is_string($rawShifts)) {
                            $decodedShifts = json_decode($rawShifts, true);
                            $rawShifts = is_array($decodedShifts) ? $decodedShifts : [];
                        }
                        $shifts = is_array($rawShifts) ? $rawShifts : [];
                        $hariList = $row['hari_list'] ?? null;
                        if (! is_array($hariList)) {
                            $hariList = preg_split('/\s*,\s*/', (string) ($row['hari'] ?? ''), -1, PREG_SPLIT_NO_EMPTY) ?: [];
                        }
                        ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><strong><?= esc($hariList !== [] ? implode(', ', $hariList) : '-') ?></strong></td>
                            <td><?= esc((string) count($shifts)) ?> jadwal</td>
                            <td>
                                <?php if ($shifts !== []): ?>
                                    <?php foreach ($shifts as $shift): ?>
                                        <div>
                                            <strong><?= esc((string) ($shift['nama'] ?? 'Jadwal')) ?></strong>:
                                            Masuk <?= esc((string) ($shift['masuk_awal'] ?? '-')) ?>-<?= esc((string) ($shift['masuk_akhir'] ?? '-')) ?>,
                                            Pulang <?= esc((string) ($shift['pulang_awal'] ?? '-')) ?>-<?= esc((string) ($shift['pulang_akhir'] ?? '-')) ?>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="actions">
                                    <a href="<?= base_url('jadwal/edit/' . $row['id_jadwal']) ?>">Edit</a>
                                    <a class="danger" href="<?= base_url('jadwal/hapus/' . $row['id_jadwal']) ?>" onclick="return confirm('Yakin hapus jadwal ini?')">Hapus</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5">Belum ada jadwal masuk dan keluar.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?= view('partials/app_end') ?>
