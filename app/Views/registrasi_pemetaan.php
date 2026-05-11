<?php $kelasSiswaSelected = (string) old('kelas_siswa', ''); ?>
<?= view('partials/app_start', [
    'title' => 'Pemetaan Registrasi',
    'activeNav' => 'registrasi',
]) ?>

<div class="page-toolbar">
    <div class="page-toolbar-title">
        <h2>Pemetaan Registrasi</h2>
        <p>Kaitkan calon registrasi pending ke data siswa atau guru yang sudah ada.</p>
    </div>
    <a class="btn btn-muted" href="<?= base_url('admin/registrasi') ?>">Kembali ke Registrasi</a>
</div>

<section class="panel form-card">
    <h3>Form Pemetaan</h3>
    <form action="<?= base_url('admin/registrasi/pemetaan/simpan') ?>" method="post" class="form-grid" id="mapping-form">
        <input type="hidden" name="target_id" id="target_id" value="<?= esc(old('target_id', '')) ?>">

        <div class="field">
            <label for="candidate_id">Calon Registrasi</label>
            <select id="candidate_id" name="candidate_id" required>
                <option value="">Pilih calon registrasi</option>
                <?php foreach ($pendingList as $row): ?>
                    <option value="<?= (int) $row['id_candidate'] ?>" <?= (string) old('candidate_id') === (string) $row['id_candidate'] ? 'selected' : '' ?>>
                        #<?= (int) $row['id_candidate'] ?> - <?= esc((string) $row['nama_registrasi']) ?>
                        <?= ! empty($row['id_rfid']) ? ' | RFID: ' . esc((string) $row['id_rfid']) : '' ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="field">
            <label for="target_type">Pemetaan Ke</label>
            <select id="target_type" name="target_type" required>
                <?php $oldTargetType = old('target_type', 'siswa'); ?>
                <option value="siswa" <?= $oldTargetType === 'siswa' ? 'selected' : '' ?>>Siswa</option>
                <option value="guru" <?= $oldTargetType === 'guru' ? 'selected' : '' ?>>Guru</option>
            </select>
        </div>

        <div id="siswa-fields" class="form-grid">
            <div class="field">
                <label for="kelas_filter">Filter Kelas Siswa</label>
                <select id="kelas_filter">
                    <option value="">Semua kelas</option>
                    <?php foreach ($kelasList as $kelas): ?>
                        <option value="<?= esc((string) $kelas) ?>"><?= esc((string) $kelas) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field">
                <label for="siswa_target">Pilih Siswa</label>
                <select id="siswa_target">
                    <option value="">Pilih siswa</option>
                    <?php foreach ($siswaList as $row): ?>
                        <option value="<?= (int) $row['id'] ?>" data-kelas="<?= esc((string) ($row['kelas'] ?? '')) ?>">
                            <?= esc((string) $row['nama']) ?> (<?= esc((string) $row['no_induk']) ?>) - <?= esc((string) ($row['kelas'] ?? '-')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field">
                <label for="kelas_siswa">Set/Ubah Kelas Siswa (Opsional)</label>
                <select id="kelas_siswa" name="kelas_siswa">
                    <option value="">Tetap kelas saat ini</option>
                    <?php foreach ($kelasList as $kelas): ?>
                        <option value="<?= esc((string) $kelas) ?>" <?= $kelasSiswaSelected === (string) $kelas ? 'selected' : '' ?>>
                            <?= esc((string) $kelas) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div id="guru-fields" class="form-grid" hidden>
            <div class="field">
                <label for="guru_target">Pilih Guru</label>
                <select id="guru_target">
                    <option value="">Pilih guru</option>
                    <?php foreach ($guruList as $row): ?>
                        <option value="<?= (int) $row['id_guru'] ?>">
                            <?= esc((string) $row['nama']) ?> (<?= esc((string) $row['nip']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="btn-group">
            <button class="btn btn-primary" type="submit">Simpan Pemetaan</button>
            <a class="btn btn-muted" href="<?= base_url('admin/registrasi') ?>">Kembali</a>
        </div>
    </form>
</section>

<section class="panel">
    <h3>Calon Registrasi Pending</h3>
    <div class="table-wrap">
        <table class="data-table compact">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nama</th>
                    <th>RFID</th>
                    <th>Wajah</th>
                    <th>Dibuat</th>
                </tr>
            </thead>
            <tbody>
                <?php if (! empty($pendingList)): ?>
                    <?php foreach ($pendingList as $row): ?>
                        <tr>
                            <td>#<?= (int) $row['id_candidate'] ?></td>
                            <td><?= esc((string) $row['nama_registrasi']) ?></td>
                            <td><?= esc((string) (($row['id_rfid'] ?? '') !== '' ? $row['id_rfid'] : '-')) ?></td>
                            <td><?= ! empty($row['foto_wajah']) ? 'Ada' : 'Tidak ada' ?></td>
                            <td><?= esc((string) ($row['created_at'] ?? '-')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5">Tidak ada data pending.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="panel">
    <h3>Riwayat Pemetaan Terbaru</h3>
    <div class="table-wrap">
        <table class="data-table compact">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nama</th>
                    <th>Tujuan</th>
                    <th>Mapped At</th>
                </tr>
            </thead>
            <tbody>
                <?php if (! empty($mappedRecent)): ?>
                    <?php foreach ($mappedRecent as $row): ?>
                        <tr>
                            <td>#<?= (int) $row['id_candidate'] ?></td>
                            <td><?= esc((string) $row['nama_registrasi']) ?></td>
                            <td><?= esc(strtoupper((string) ($row['mapped_target_type'] ?? '-'))) ?> #<?= esc((string) ($row['mapped_target_id'] ?? '-')) ?></td>
                            <td><?= esc((string) ($row['mapped_at'] ?? '-')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4">Belum ada data pemetaan.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<script>
    const targetTypeEl = document.getElementById('target_type');
    const targetIdEl = document.getElementById('target_id');
    const siswaFields = document.getElementById('siswa-fields');
    const guruFields = document.getElementById('guru-fields');
    const siswaTarget = document.getElementById('siswa_target');
    const guruTarget = document.getElementById('guru_target');
    const kelasFilter = document.getElementById('kelas_filter');

    function applyTypeVisibility() {
        const type = targetTypeEl.value;
        siswaFields.hidden = type === 'guru';
        guruFields.hidden = type !== 'guru';
        updateTargetId();
    }

    function updateTargetId() {
        targetIdEl.value = targetTypeEl.value === 'guru'
            ? (guruTarget.value || '')
            : (siswaTarget.value || '');
    }

    function filterSiswaByKelas() {
        const kelas = (kelasFilter.value || '').trim();
        Array.from(siswaTarget.options).forEach((opt, index) => {
            if (index === 0) {
                opt.hidden = false;
                return;
            }
            const optKelas = (opt.dataset.kelas || '').trim();
            opt.hidden = kelas !== '' && optKelas !== kelas;
        });

        if (siswaTarget.selectedOptions.length && siswaTarget.selectedOptions[0].hidden) {
            siswaTarget.value = '';
        }
        updateTargetId();
    }

    targetTypeEl.addEventListener('change', applyTypeVisibility);
    siswaTarget.addEventListener('change', updateTargetId);
    guruTarget.addEventListener('change', updateTargetId);
    kelasFilter.addEventListener('change', filterSiswaByKelas);

    applyTypeVisibility();
    filterSiswaByKelas();
</script>

<?= view('partials/app_end') ?>
