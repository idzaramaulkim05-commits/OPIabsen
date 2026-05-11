<?= view('partials/auth_start', [
    'title' => 'Login - SmartPresence',
    'navTarget' => '',
    'navLabel' => 'Beranda',
]) ?>

<main class="login-shell">
    <section class="login-card">
        <h2>Masuk ke SmartPresence</h2>
        <p class="login-subtitle">Gunakan akun admin atau guru yang sudah didaftarkan.</p>

        <?php if (session()->getFlashdata('error')): ?>
            <div class="alert-error" role="alert"><?= esc(session()->getFlashdata('error')) ?></div>
        <?php endif; ?>

        <form action="<?= base_url('login') ?>" method="post">
            <div class="field-block">
                <label for="username">Username</label>
                <input id="username" type="text" name="username" value="<?= esc(old('username')) ?>" required>
            </div>

            <div class="field-block">
                <label for="password">Password</label>
                <input id="password" type="password" name="password" required>
            </div>

            <div class="field-block">
                <label for="captcha">Masukkan Captcha</label>
                <input id="captcha" type="text" name="captcha" inputmode="numeric" required>
            </div>

            <div class="captcha-box"><?= esc((string) $captcha) ?></div>

            <button type="submit" class="auth-btn primary block">Login</button>

            <p class="helper-text">Menu dan laporan otomatis mengikuti role akun yang digunakan.</p>
        </form>
    </section>
</main>

<?= view('partials/auth_end') ?>
