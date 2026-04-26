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
                    <label for="username">Username</label>
                    <input id="username" type="text" name="username" value="<?= esc(old('username', $akun['username'] ?? '')) ?>" required>
                </div>

                <div class="field">
                    <label for="password">Password <?= $akun ? '(Kosongkan jika tidak diubah)' : '' ?></label>
                    <input id="password" type="password" name="password" <?= $akun ? '' : 'required' ?>>
                </div>

                <div class="btn-group">
                    <button class="btn btn-primary" type="submit">Simpan</button>
                    <a class="btn btn-muted" href="<?= base_url('admin/akun') ?>">Kembali ke Kelola Akun Admin</a>
                </div>
            </form>
        </section>
    </main>
</body>
</html>
