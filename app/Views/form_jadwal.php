<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title) ?></title>
    <link rel="stylesheet" href="<?= base_url('app-theme.css') ?>">
</head>
<body class="app-page">
    <?php $selectedKelas = (string) old('kelas', $jadwal['kelas'] ?? ''); ?>
    <header class="app-topbar">
        <div class="app-topbar-inner">
            <div class="brand-stack">
                <span class="brand-kicker">SmartPresence</span>
                <h1 class="brand-title"><?= esc($title) ?></h1>
            </div>
            <div class="topbar-meta">
                <span class="user-pill"><?= esc((string) (session()->get('nama') ?: session()->get('username'))) ?></span>
                <a class="btn btn-ghost" href="<?= base_url('logout') ?>">Logout</a>
            </div>
        </div>
    </header>

    <main class="app-shell">
        <?php if (session()->getFlashdata('error')): ?>
            <div class="flash error"><?= esc(session()->getFlashdata('error')) ?></div>
        <?php endif; ?>

        <section class="panel form-card">
            <form action="<?= esc($action) ?>" method="post" class="form-grid">
                <div class="field">
                    <label for="id_guru">Guru Pengajar</label>
                    <select id="id_guru" name="id_guru" required>
                        <option value="">Pilih Guru</option>
                        <?php foreach ($guruList as $guru): ?>
                            <option value="<?= esc((string) $guru['id_guru']) ?>" <?= (int) old('id_guru', $jadwal['id_guru'] ?? 0) === (int) $guru['id_guru'] ? 'selected' : '' ?>>
                                <?= esc($guru['nama']) ?> (<?= esc($guru['nip']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field">
                    <label for="kelas">Kelas</label>
                    <select id="kelas" name="kelas" required>
                        <option value="">Pilih Kelas</option>
                        <?php foreach (($kelasOptions ?? []) as $kelas): ?>
                            <option value="<?= esc((string) $kelas) ?>" <?= $selectedKelas === (string) $kelas ? 'selected' : '' ?>>
                                <?= esc((string) $kelas) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field">
                    <label for="mata_pelajaran">Mata Pelajaran</label>
                    <input id="mata_pelajaran" type="text" name="mata_pelajaran" value="<?= esc(old('mata_pelajaran', $jadwal['mata_pelajaran'] ?? '')) ?>" required>
                </div>

                <div class="field">
                    <label for="hari">Hari</label>
                    <select id="hari" name="hari" required>
                        <option value="">Pilih Hari</option>
                        <?php foreach ($hariList as $hari): ?>
                            <option value="<?= esc($hari) ?>" <?= old('hari', $jadwal['hari'] ?? '') === $hari ? 'selected' : '' ?>><?= esc($hari) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field">
                    <label for="jam_mulai">Jam Mulai</label>
                    <input id="jam_mulai" type="time" name="jam_mulai" value="<?= esc(old('jam_mulai', $jadwal['jam_mulai'] ?? '')) ?>" required>
                </div>

                <div class="field">
                    <label for="jam_selesai">Jam Selesai</label>
                    <input id="jam_selesai" type="time" name="jam_selesai" value="<?= esc(old('jam_selesai', $jadwal['jam_selesai'] ?? '')) ?>" required>
                </div>

                <div class="btn-group">
                    <button class="btn btn-primary" type="submit">Simpan</button>
                    <a class="btn btn-muted" href="<?= base_url('jadwal') ?>">Kembali ke Data Jadwal</a>
                </div>
            </form>
        </section>
    </main>
</body>
</html>
