<?= view('partials/auth_start', [
    'title' => 'SmartPresence',
    'bodyClass' => 'landing-page',
    'showLandingNav' => true,
    'navTarget' => 'login',
    'navLabel' => 'Login',
]) ?>

<main class="hero-shell" id="fitur">
    <section class="hero-grid">
        <article class="hero-main">
            <h1>SmartPresence</h1>
            <p>Sistem presensi sekolah berbasis role, RFID, dan verifikasi wajah untuk menjaga data kehadiran tetap rapi, mudah dipantau, dan siap dilaporkan.</p>
            <div class="hero-actions">
                <a class="auth-btn primary" href="<?= base_url('login') ?>">Masuk ke Sistem</a>
                <a class="auth-btn light" href="#alur">Lihat Alur</a>
            </div>
        </article>

        <aside class="hero-side">
            <div class="info-card" id="alur">
                <h3>Alur Presensi</h3>
                <ul>
                    <li>Admin mengelola akun, siswa, guru, kelas, dan jadwal.</li>
                    <li>RFID serta wajah didaftarkan ke profil siswa atau guru.</li>
                    <li>Alat IoT mengirim data presensi ke sistem.</li>
                    <li>Laporan dapat difilter dan dicetak sesuai hak akses.</li>
                </ul>
            </div>

            <div class="info-card" id="keamanan">
                <h3>Kontrol Akses</h3>
                <ul>
                    <li>Admin memiliki akses penuh untuk data master.</li>
                    <li>Guru melihat presensi sesuai akun dan kelas terkait.</li>
                    <li>Riwayat presensi tersusun untuk kebutuhan audit.</li>
                </ul>
            </div>
        </aside>
    </section>
</main>

<?= view('partials/auth_end') ?>
