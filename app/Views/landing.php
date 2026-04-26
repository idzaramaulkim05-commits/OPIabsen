<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartPresence</title>
    <link rel="stylesheet" href="<?= base_url('auth-theme.css') ?>">
</head>
<body class="auth-page landing-page">
    <div class="auth-backdrop"></div>

    <header class="site-nav">
        <div class="site-nav-inner">
            <div class="brand-mark">
                <span>Sistem Presensi Sekolah</span>
                <strong>SmartPresence</strong>
            </div>

            <nav class="center-links">
                <a href="#fitur">Fitur</a>
                <a href="#langkah">Langkah Presensi</a>
                <a href="#keamanan">Keamanan</a>
            </nav>

            <a href="<?= base_url('login') ?>" class="auth-btn light">Login</a>
        </div>
    </header>

    <main class="hero-shell" id="fitur">
        <section class="hero-grid">
            <article class="hero-main">
                <h1>Sistem Presensi Digital berbasis Role dan Verifikasi Identitas</h1>
                <p>SmartPresence mempermudah presensi harian, menjaga konsistensi data kelas, dan memastikan akses admin serta guru berjalan sesuai hak masing-masing.</p>
                <div class="hero-actions">
                    <a class="auth-btn primary" href="<?= base_url('login') ?>">Masuk ke Sistem</a>
                    <a class="auth-btn light" href="#fitur">Lihat Fitur</a>
                </div>
            </article>

            <aside class="hero-side">
                <div id="langkah" class="info-card">
                    <h3>Langkah Presensi</h3>
                    <ul>
                        <li>Guru login sesuai akun masing-masing.</li>
                        <li>Sistem menampilkan kelas pada jadwal aktif.</li>
                        <li>Presensi dicatat per siswa secara real-time.</li>
                        <li>Laporan dapat dicetak sesuai hak akses role.</li>
                    </ul>
                </div>

                <div id="keamanan" class="info-card">
                    <h3>Keamanan Sistem</h3>
                    <ul>
                        <li>Akses dibatasi ketat sesuai role admin dan guru.</li>
                        <li>Data wajah dan RFID terikat ke profil pengguna.</li>
                        <li>Laporan wali kelas otomatis dibatasi per kelas.</li>
                        <li>Riwayat presensi tersimpan rapi untuk audit.</li>
                    </ul>
                </div>
            </aside>
        </section>
    </main>
</body>
</html>
