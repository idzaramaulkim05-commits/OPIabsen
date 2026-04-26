<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SmartPresence</title>
    <link rel="stylesheet" href="<?= base_url('auth-theme.css') ?>">
</head>
<body class="auth-page">
    <div class="auth-backdrop"></div>

    <header class="site-nav">
        <div class="site-nav-inner">
            <div class="brand-mark">
                <span>Sistem Presensi Sekolah</span>
                <strong>SmartPresence</strong>
            </div>
            <a class="auth-btn light" href="<?= base_url('/') ?>">Kembali ke Beranda</a>
        </div>
    </header>

    <main class="login-shell">
        <section class="login-card">
            <h2>Masuk ke SmartPresence</h2>
            <p class="login-subtitle">Gunakan akun admin atau guru yang sudah didaftarkan.</p>

            <?php if (session()->getFlashdata('error')): ?>
                <div class="alert-error"><?= esc(session()->getFlashdata('error')) ?></div>
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
                    <input id="captcha" type="text" name="captcha" required>
                </div>

                <div class="captcha-box"><?= esc((string) $captcha) ?></div>

                <button type="submit" class="auth-btn primary block">Login</button>

                <p class="helper-text">Akses ke menu dan laporan akan otomatis mengikuti role akun yang Anda gunakan.</p>
            </form>
        </section>
    </main>
</body>
</html>
