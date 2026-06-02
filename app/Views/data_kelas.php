<?= view('partials/app_start', [
    'title' => 'Master Data Kelas',
    'activeNav' => 'kelas',
]) ?>

<div class="page-toolbar">
    <div class="page-toolbar-title">
        <h2>Master Data Kelas</h2>
        <p>Kelola daftar kelas yang dipakai pada data siswa dan guru.</p>
    </div>
</div>

<section class="panel form-card">
    <h3>Tambah Kelas</h3>
    <form action="<?= base_url('master-data/kelas/simpan') ?>" method="post" class="form-grid">
        <?= csrf_field() ?>
        <div class="field">
            <label for="nama_kelas">Nama Kelas</label>
            <input
                id="nama_kelas"
                type="text"
                name="nama_kelas"
                value="<?= esc(old('nama_kelas', '')) ?>"
                placeholder="Contoh: XI-RPL-1"
                required
            >
        </div>
        <div class="btn-group">
            <button class="btn btn-primary" type="submit">Simpan Kelas</button>
        </div>
    </form>
</section>

<section class="panel">
    <h3>Daftar Kelas</h3>
    <div class="table-wrap">
        <table class="data-table compact">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Kelas</th>
                    <th>Dipakai</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (! empty($kelas)): ?>
                    <?php $no = 1; ?>
                    <?php foreach ($kelas as $row): ?>
                        <?php
                        $namaKelas = (string) ($row['nama_kelas'] ?? '');
                        $usedCount = (int) ($usageMap[$namaKelas] ?? 0);
                        ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= esc($namaKelas) ?></td>
                            <td><?= esc((string) $usedCount) ?></td>
                            <td>
                                <form action="<?= base_url('master-data/kelas/update/' . (int) $row['id_kelas']) ?>" method="post" class="inline-controls">
                                    <?= csrf_field() ?>
                                    <input class="inline-field" type="text" name="nama_kelas" value="<?= esc($namaKelas) ?>" required>
                                    <button class="btn btn-secondary" type="submit">Update</button>
                                    <?php if ($usedCount > 0): ?>
                                        <button class="btn btn-muted" type="button" disabled>Dipakai</button>
                                        <span class="helper">Tidak bisa dihapus karena masih dipakai siswa.</span>
                                    <?php else: ?>
                                        <a class="btn btn-danger" href="<?= base_url('master-data/kelas/hapus/' . (int) $row['id_kelas']) ?>" onclick="return confirm('Yakin hapus kelas ini?')">Hapus</a>
                                    <?php endif; ?>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4">Belum ada data kelas di master data.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?= view('partials/app_end') ?>
