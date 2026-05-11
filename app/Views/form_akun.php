<?= view('partials/app_start', [
    'title' => (string) ($title ?? 'Form Akun'),
    'activeNav' => 'akun',
]) ?>

<section class="panel form-card">
    <h3><?= esc($title ?? 'Form Akun') ?></h3>
    <form action="<?= esc($action) ?>" method="post" class="form-grid">
        <?php
        $currentRole = (string) old('role', (string) ($akun['role'] ?? 'admin'));
        $isEditing = $akun !== null;
        ?>
        <div class="field">
            <label for="role">Role</label>
            <select id="role" name="role" <?= $isEditing ? 'disabled' : '' ?>>
                <option value="admin" <?= $currentRole === 'admin' ? 'selected' : '' ?>>Admin</option>
                <option value="guru" <?= $currentRole === 'guru' ? 'selected' : '' ?>>Guru</option>
            </select>
            <?php if ($isEditing): ?>
                <input type="hidden" name="role" value="<?= esc($currentRole) ?>">
            <?php endif; ?>
        </div>

        <div class="field" id="namaField" <?= $currentRole === 'guru' ? '' : 'hidden' ?>>
            <label for="nama">Nama Guru</label>
            <input id="nama" type="text" name="nama" value="<?= esc(old('nama', $akun['nama'] ?? '')) ?>">
        </div>

        <div class="field">
            <label for="username">Username</label>
            <input id="username" type="text" name="username" value="<?= esc(old('username', $akun['username'] ?? '')) ?>" required>
        </div>

        <div class="field">
            <label for="password">Password <?= $akun ? '(Kosongkan jika tidak diubah)' : '' ?></label>
            <input id="password" type="password" name="password" <?= $akun ? '' : 'required' ?>>
        </div>

        <div class="btn-group">
            <button class="btn btn-primary" type="submit">Simpan</button>
            <a class="btn btn-muted" href="<?= base_url('admin/akun') ?>">Kembali</a>
        </div>
    </form>
</section>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const roleEl = document.getElementById('role');
    const namaField = document.getElementById('namaField');
    const namaInput = document.getElementById('nama');
    if (!roleEl || !namaField || !namaInput) return;

    const syncRole = () => {
        const isGuru = roleEl.value === 'guru';
        namaField.hidden = !isGuru;
        namaInput.required = isGuru;
        if (!isGuru) namaInput.value = '';
    };

    roleEl.addEventListener('change', syncRole);
    syncRole();
});
</script>

<?= view('partials/app_end') ?>
