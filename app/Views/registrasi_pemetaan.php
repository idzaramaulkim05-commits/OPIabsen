<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pemetaan Registrasi</title>
    <link rel="stylesheet" href="<?= base_url('app-theme.css') ?>">
</head>
<body class="app-page">
    <?php $kelasSiswaSelected = (string) old('kelas_siswa', ''); ?>
    <header class="app-topbar">
        <div class="app-topbar-inner">
            <div class="brand-stack">
                <span class="brand-kicker">SmartPresence</span>
                <h1 class="brand-title">Pemetaan Registrasi</h1>
            </div>
            <div class="topbar-meta">
                <span class="user-pill"><?= esc((string) (session()->get('nama') ?: session()->get('username'))) ?></span>
                <a class="btn btn-ghost" href="<?= base_url('logout') ?>">Logout</a>
            </div>
        </div>
    </header>

    <main class="app-shell">
        <div class="nav-pills">
            <a href="<?= base_url('dashboard') ?>">Dashboard</a>
            <a href="<?= base_url('siswa/data') ?>">Data Siswa</a>
            <a href="<?= base_url('guru') ?>">Data Guru</a>
            <a href="<?= base_url('admin/registrasi') ?>">Registrasi Wajah & RFID</a>
            <a class="primary" href="<?= base_url('admin/registrasi/pemetaan') ?>">Pemetaan Registrasi</a>
            <a href="<?= base_url('jadwal') ?>">Jadwal</a>
            <a href="<?= base_url('master-data/kelas') ?>">Master Data Kelas</a>
        </div>

        <?php if (session()->getFlashdata('error')): ?>
            <div class="flash error"><?= esc(session()->getFlashdata('error')) ?></div>
        <?php endif; ?>

        <?php if (session()->getFlashdata('success')): ?>
            <div class="flash success"><?= esc(session()->getFlashdata('success')) ?></div>
        <?php endif; ?>

        <section class="panel form-card">
            <h3>Form Pemetaan</h3>
            <p class="page-note">Tahap ini mengaitkan calon registrasi (nama + RFID/wajah) ke data guru/siswa yang sudah ada.</p>

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

                <div id="siswa-fields">
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
                                <option
                                    value="<?= (int) $row['id'] ?>"
                                    data-kelas="<?= esc((string) ($row['kelas'] ?? '')) ?>"
                                >
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

                <div id="guru-fields">
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
                    <a class="btn btn-muted" href="<?= base_url('admin/registrasi') ?>">Kembali ke Registrasi</a>
                </div>
            </form>
        </section>

        <section class="panel">
            <h3>Daftar Calon Registrasi Pending</h3>
            <div class="table-wrap">
                <table class="data-table">
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
                <table class="data-table">
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
    </main>

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
            if (type === 'guru') {
                siswaFields.style.display = 'none';
                guruFields.style.display = 'block';
                targetIdEl.value = guruTarget.value || '';
            } else {
                siswaFields.style.display = 'block';
                guruFields.style.display = 'none';
                targetIdEl.value = siswaTarget.value || '';
            }
        }

        function updateTargetId() {
            if (targetTypeEl.value === 'guru') {
                targetIdEl.value = guruTarget.value || '';
            } else {
                targetIdEl.value = siswaTarget.value || '';
            }
        }

        function filterSiswaByKelas() {
            const kelas = (kelasFilter.value || '').trim();
            const options = Array.from(siswaTarget.options);
            options.forEach((opt, idx) => {
                if (idx === 0) {
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
</body>
</html>
