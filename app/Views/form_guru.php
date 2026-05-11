<?php
$selectedKelasWali = (string) old('kelas_wali', $guru['kelas_wali'] ?? '');
?>
<?= view('partials/app_start', [
    'title' => (string) ($title ?? 'Form Guru'),
    'activeNav' => 'guru',
]) ?>

<section class="panel form-card">
    <h3><?= esc($title ?? 'Form Guru') ?></h3>
    <form action="<?= esc($action) ?>" method="post" class="form-grid">
        <div class="field">
            <label for="nama">Nama Guru</label>
            <input id="nama" type="text" name="nama" value="<?= esc(old('nama', $guru['nama'] ?? '')) ?>" required>
        </div>

        <div class="field">
            <label for="nip">NIP (Opsional)</label>
            <input id="nip" type="text" name="nip" value="<?= esc(old('nip', $guru['nip'] ?? '')) ?>">
        </div>

        <div class="field">
            <label for="username">Username Login Guru</label>
            <input id="username" type="text" name="username" value="<?= esc(old('username', $guru['username'] ?? '')) ?>" required>
        </div>

        <div class="field">
            <label for="password">Password <?= $guru ? '(Kosongkan jika tidak diubah)' : '' ?></label>
            <input id="password" type="password" name="password" <?= $guru ? '' : 'required' ?>>
        </div>

        <div class="checkbox-row">
            <input id="is_wali_kelas" type="checkbox" name="is_wali_kelas" value="1" <?= (int) old('is_wali_kelas', $guru['is_wali_kelas'] ?? 0) === 1 ? 'checked' : '' ?>>
            <label for="is_wali_kelas">Jadikan Wali Kelas</label>
        </div>

        <div class="field">
            <label for="kelas_wali">Kelas Wali</label>
            <select id="kelas_wali" name="kelas_wali">
                <option value="">Pilih Kelas Wali</option>
                <?php foreach (($kelasOptions ?? []) as $kelas): ?>
                    <option value="<?= esc((string) $kelas) ?>" <?= $selectedKelasWali === (string) $kelas ? 'selected' : '' ?>>
                        <?= esc((string) $kelas) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="btn-group">
            <button class="btn btn-primary" type="submit">Simpan</button>
            <a class="btn btn-muted" href="<?= base_url('guru') ?>">Kembali</a>
        </div>
    </form>
</section>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const isWaliCheckbox = document.getElementById('is_wali_kelas');
    const kelasWaliInput = document.getElementById('kelas_wali');
    if (!isWaliCheckbox || !kelasWaliInput) return;

    const syncKelasWaliRequirement = () => {
        kelasWaliInput.required = isWaliCheckbox.checked;
        if (!isWaliCheckbox.checked) {
            kelasWaliInput.value = '';
        }
    };

    isWaliCheckbox.addEventListener('change', syncKelasWaliRequirement);
    syncKelasWaliRequirement();
});
</script>

<?= view('partials/app_end') ?>

