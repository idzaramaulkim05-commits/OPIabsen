<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title) ?></title>
    <link rel="stylesheet" href="<?= base_url('app-theme.css') ?>">
</head>
<body class="app-page">
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
                    <label for="nama">Nama Guru</label>
                    <input id="nama" type="text" name="nama" value="<?= esc(old('nama', $guru['nama'] ?? '')) ?>" required>
                </div>

                <div class="field">
                    <label for="nip">NIP</label>
                    <input id="nip" type="text" name="nip" value="<?= esc(old('nip', $guru['nip'] ?? '')) ?>" required>
                </div>

                <div class="field">
                    <label for="username">Username Login Guru</label>
                    <input id="username" type="text" name="username" value="<?= esc(old('username', $guru['username'] ?? '')) ?>" required>
                </div>

                <div class="field">
                    <label for="password">Password <?= $guru ? '(Kosongkan jika tidak diubah)' : '' ?></label>
                    <input id="password" type="password" name="password" <?= $guru ? '' : 'required' ?>>
                </div>

                <div class="field">
                    <label for="id_rfid">ID RFID Guru</label>
                    <input id="id_rfid" type="text" name="id_rfid" value="<?= esc(old('id_rfid', $guru['id_rfid'] ?? '')) ?>" placeholder="Contoh: RFID-GURU-002">
                </div>

                <div class="checkbox-row">
                    <input id="is_wali_kelas" type="checkbox" name="is_wali_kelas" value="1" <?= (int) old('is_wali_kelas', $guru['is_wali_kelas'] ?? 0) === 1 ? 'checked' : '' ?>>
                    <label for="is_wali_kelas">Jadikan Wali Kelas</label>
                </div>

                <div class="field">
                    <label for="kelas_wali">Kelas Wali</label>
                    <input id="kelas_wali" type="text" name="kelas_wali" value="<?= esc(old('kelas_wali', $guru['kelas_wali'] ?? '')) ?>" placeholder="Contoh: X-IPA-1">
                </div>

                <div class="field">
                    <label>Registrasi Wajah Guru</label>
                    <div class="preview-box">
                        <video id="camera" autoplay></video>
                        <img id="preview" src="<?= esc(old('foto_wajah', $guru['foto_wajah'] ?? '')) ?>" alt="Preview Wajah">
                    </div>
                    <div class="btn-group">
                        <button class="btn btn-secondary" type="button" onclick="ambilFoto()">Ambil Foto Wajah</button>
                    </div>
                    <canvas id="canvas" style="display:none;"></canvas>
                </div>

                <input type="hidden" name="foto_wajah" id="foto_wajah" value="<?= esc(old('foto_wajah', $guru['foto_wajah'] ?? '')) ?>">

                <div class="btn-group">
                    <button class="btn btn-primary" type="submit">Simpan</button>
                    <a class="btn btn-muted" href="<?= base_url('guru') ?>">Kembali ke Data Guru</a>
                </div>
            </form>
        </section>
    </main>

    <script>
        const video = document.getElementById('camera');
        const preview = document.getElementById('preview');
        const isWaliCheckbox = document.getElementById('is_wali_kelas');
        const kelasWaliInput = document.getElementById('kelas_wali');

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

        function syncKelasWaliRequirement() {
            kelasWaliInput.required = isWaliCheckbox.checked;
            if (!isWaliCheckbox.checked) {
                kelasWaliInput.value = '';
            }
        }

        isWaliCheckbox.addEventListener('change', syncKelasWaliRequirement);
        syncKelasWaliRequirement();
    </script>
</body>
</html>
