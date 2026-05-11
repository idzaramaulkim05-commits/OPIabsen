<?php
$pageTitle = (string) ($title ?? 'SmartPresence');
$activeNav = (string) ($activeNav ?? '');
$shellClass = trim((string) ($shellClass ?? ''));
$bodyClass = trim('app-page ' . (string) ($bodyClass ?? ''));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($pageTitle) ?> - SmartPresence</title>
    <link rel="stylesheet" href="<?= base_url('app-theme.css') ?>">
</head>
<body class="<?= esc($bodyClass) ?>">
    <?= view('partials/topbar', ['title' => $pageTitle, 'activeNav' => $activeNav]) ?>

    <main class="app-shell<?= $shellClass !== '' ? ' ' . esc($shellClass) : '' ?>">
        <?= view('partials/flash') ?>
