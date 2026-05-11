<?= view('partials/auth_start', [
    'title' => 'Verifikasi - SmartPresence',
    'navTarget' => 'login',
    'navLabel' => 'Login',
]) ?>

<main class="login-shell">
    <section class="login-card">
        <h2>Verifikasi</h2>
        <p class="login-subtitle">Masukkan kode atau data verifikasi yang diminta sistem.</p>

        <?php if (session()->getFlashdata('error')): ?>
            <div class="alert-error" role="alert"><?= esc(session()->getFlashdata('error')) ?></div>
        <?php endif; ?>

        <form method="post" action="<?= base_url('verifyProcess') ?>">
            <div class="field-block">
                <label for="kode">Kode Verifikasi</label>
                <input id="kode" type="text" name="kode" required>
            </div>

            <button class="auth-btn primary block" type="submit">Verifikasi</button>
        </form>
    </section>
</main>

<?= view('partials/auth_end') ?>
