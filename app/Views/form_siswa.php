<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title) ?></title>
    <link rel="stylesheet" href="<?= base_url('app-theme.css') ?>">
</head>
<body class="app-page">
    <?php $selectedKelas = (string) old('kelas', $siswa['kelas'] ?? ''); ?>
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
                    <label for="nama">Nama</label>
                    <input id="nama" type="text" name="nama" value="<?= esc(old('nama', $siswa['nama'] ?? '')) ?>" required>
                </div>

                <div class="field">
                    <label for="no_induk">No Induk</label>
                    <input id="no_induk" type="text" name="no_induk" value="<?= esc(old('no_induk', $siswa['no_induk'] ?? '')) ?>" placeholder="Opsional (NIS/NISN)">
                </div>

                <div class="field">
                    <label for="kelas">Kelas</label>
                    <select id="kelas" name="kelas">
                        <option value="">Belum ditentukan</option>
                        <?php foreach (($kelasOptions ?? []) as $kelas): ?>
                            <option value="<?= esc((string) $kelas) ?>" <?= $selectedKelas === (string) $kelas ? 'selected' : '' ?>>
                                <?= esc((string) $kelas) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="helper">Daftar kelas diatur dari menu Master Data Kelas.</div>
                </div>

                <div class="field">
                    <label for="alamat">Alamat</label>
                    <textarea id="alamat" name="alamat" rows="2" placeholder="Opsional"><?= esc(old('alamat', $siswa['alamat'] ?? '')) ?></textarea>
                </div>

                <div class="field">
                    <label for="id_rfid">ID RFID</label>
                    <input id="id_rfid" type="text" name="id_rfid" value="<?= esc(old('id_rfid', $siswa['id_rfid'] ?? '')) ?>" placeholder="Contoh: RFID-SISWA-001">
                </div>

                <div class="field">
                    <label>Registrasi Wajah (Kamera)</label>
                    <div class="preview-box">
                        <video id="camera" autoplay></video>
                        <img id="preview" src="<?= esc(old('foto_wajah', $siswa['foto_wajah'] ?? '')) ?>" alt="Preview Wajah">
                    </div>
                    <div class="btn-group">
                        <button class="btn btn-secondary" type="button" onclick="ambilFoto()">Ambil Foto Wajah</button>
                    </div>
                    <canvas id="canvas" style="display:none;"></canvas>
                </div>

                <input type="hidden" name="foto_wajah" id="foto_wajah" value="<?= esc(old('foto_wajah', $siswa['foto_wajah'] ?? '')) ?>">

                <div class="btn-group">
                    <button class="btn btn-primary" type="submit">Simpan</button>
                    <a class="btn btn-muted" href="<?= base_url('siswa/data') ?>">Kembali ke Data Siswa</a>
                </div>
            </form>
        </section>
    </main>

    <script>
        const video = document.getElementById('camera');
        const preview = document.getElementById('preview');

        if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
            navigator.mediaDevices.getUserMedia({ video: true })
                .then((stream) => {
                    video.srcObject = stream;
                })
                .catch(() => {
                    video.style.display = 'none';
                });
        }

        function ambilFoto() {
            const canvas = document.getElementById('canvas');
            const context = canvas.getContext('2d');
            canvas.width = 320;
            canvas.height = 240;
            context.drawImage(video, 0, 0, 320, 240);
            const imageData = canvas.toDataURL('image/png');
            document.getElementById('foto_wajah').value = imageData;
            preview.src = imageData;
        }
    </script>
</body>
</html>
