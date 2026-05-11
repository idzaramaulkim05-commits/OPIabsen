<?php
$selectedKelas = (string) old('kelas', $siswa['kelas'] ?? '');
$fotoPreview = (string) old('foto_wajah', $siswa['foto_wajah'] ?? '');
?>
<?= view('partials/app_start', [
    'title' => (string) ($title ?? 'Form Siswa'),
    'activeNav' => 'siswa',
]) ?>

<section class="panel form-card">
    <h3><?= esc($title ?? 'Form Siswa') ?></h3>
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
            <label>Registrasi Wajah</label>
            <div class="preview-box">
                <video id="camera" autoplay playsinline></video>
                <img id="preview" src="<?= esc($fotoPreview) ?>" alt="Preview wajah siswa" <?= $fotoPreview === '' ? 'hidden' : '' ?>>
            </div>
            <div class="btn-group">
                <button class="btn btn-secondary" type="button" onclick="ambilFoto()">Ambil Foto Wajah</button>
            </div>
            <canvas id="canvas" class="camera-canvas"></canvas>
        </div>

        <input type="hidden" name="foto_wajah" id="foto_wajah" value="<?= esc($fotoPreview) ?>">

        <div class="btn-group">
            <button class="btn btn-primary" type="submit">Simpan</button>
            <a class="btn btn-muted" href="<?= base_url('siswa/data') ?>">Kembali</a>
        </div>
    </form>
</section>

<script>
    const video = document.getElementById('camera');
    const preview = document.getElementById('preview');

    if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
        navigator.mediaDevices.getUserMedia({video: true})
            .then((stream) => {
                video.srcObject = stream;
            })
            .catch(() => {
                video.hidden = true;
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
        preview.hidden = false;
    }
</script>

<?= view('partials/app_end') ?>
