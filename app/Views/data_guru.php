<?= view('partials/app_start', [
    'title' => 'Data Guru',
    'activeNav' => 'guru',
]) ?>

<div class="page-toolbar">
    <div class="page-toolbar-title">
        <h2>Data Guru</h2>
        <p>Kelola profil guru dan akses monitoring.</p>
    </div>
    <a class="btn btn-primary" href="<?= base_url('guru/tambah') ?>">Tambah Guru</a>
</div>

<section class="panel">
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama</th>
                    <th>NIP</th>
                    <th>Username</th>
                    <th>Wali Kelas</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (! empty($guru)): ?>
                    <?php $no = 1; ?>
                    <?php foreach ($guru as $row): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= esc((string) ($row['nama'] ?? '-')) ?></td>
                            <td><?= esc((string) (($row['nip'] ?? '') !== '' ? $row['nip'] : '-')) ?></td>
                            <td><?= esc((string) (($row['username'] ?? '') !== '' ? $row['username'] : '-')) ?></td>
                            <td><?= (int) ($row['is_wali_kelas'] ?? 0) === 1 ? 'Ya (' . esc((string) ($row['kelas_wali'] ?? '-')) . ')' : 'Tidak' ?></td>
                            <td>
                                <div class="actions">
                                    <a href="<?= base_url('guru/edit/' . $row['id_guru']) ?>">Edit</a>
                                    <a class="danger" href="<?= base_url('guru/hapus/' . $row['id_guru']) ?>" onclick="return confirm('Yakin hapus data guru ini?')">Hapus</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6">Belum ada data guru.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?= view('partials/app_end') ?>

