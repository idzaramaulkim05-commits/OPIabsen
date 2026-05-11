<?php
$pageTitle = (string) ($title ?? 'SmartPresence');
$bodyClass = trim('auth-page ' . (string) ($bodyClass ?? ''));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($pageTitle) ?></title>
    <link rel="stylesheet" href="<?= base_url('auth-theme.css') ?>">
</head>
<body class="<?= esc($bodyClass) ?>">
    <div class="auth-backdrop"></div>

    <header class="site-nav">
        <div class="site-nav-inner">
            <a class="brand-mark" href="<?= base_url('/') ?>" aria-label="SmartPresence beranda">
                <span>Sistem Presensi Sekolah</span>
                <strong>SmartPresence</strong>
            </a>

            <?php if (! empty($showLandingNav)): ?>
                <nav class="center-links" aria-label="Navigasi beranda">
                    <a href="#fitur">Fitur</a>
                    <a href="#alur">Alur</a>
                    <a href="#keamanan">Keamanan</a>
                </nav>
            <?php endif; ?>

            <a class="auth-btn light" href="<?= base_url($navTarget ?? 'login') ?>"><?= esc($navLabel ?? 'Login') ?></a>
        </div>
    </header>
