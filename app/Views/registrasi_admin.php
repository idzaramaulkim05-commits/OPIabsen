<?php
$devices = is_array($devices ?? null) ? $devices : [];
$guruList = is_array($guruList ?? null) ? $guruList : [];
$activeSession = is_array($activeSession ?? null) ? $activeSession : null;
$selectedType = in_array(($selectedType ?? 'siswa'), ['siswa', 'guru'], true) ? $selectedType : 'siswa';
$selectedTargetId = (int) ($selectedTargetId ?? 0);
$selectedSiswaName = trim((string) ($selectedSiswaName ?? ''));
$onlineWindowSec = (int) ($onlineWindowSec ?? 45);

$initialRfidValue = old('id_rfid', (string) ($initialRfid ?? ''));
$initialFaceValue = old('foto_wajah', (string) ($initialFace ?? ''));
$sessionId = (int) ($activeSession['id_session'] ?? 0);
$sessionStatus = strtolower((string) ($activeSession['status'] ?? ''));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi Wajah & RFID</title>
    <link rel="stylesheet" href="<?= base_url('app-theme.css') ?>">
    <style>
        .device-action-form {
            margin: 0;
        }
        .target-switch {
            display: none;
        }
        .target-switch.active {
            display: block;
        }
        .status-layout {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 10px;
            margin-top: 10px;
        }
        .status-block {
            border: 1px solid #d7e9e2;
            border-radius: 12px;
            padding: 10px;
            background: #f8fdfb;
        }
        .status-label {
            font-size: 12px;
            color: #5d7074;
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            font-weight: 700;
        }
        .status-value {
            font-weight: 700;
            color: #1b2c2f;
            word-break: break-word;
        }
        .face-preview-box {
            margin-top: 8px;
        }
        .face-preview-box img {
            width: 100%;
            max-width: 260px;
            border-radius: 12px;
            border: 1px solid #cde2dc;
            background: #f6fbf9;
        }
    </style>
</head>
<body class="app-page">
    <header class="app-topbar">
        <div class="app-topbar-inner">
            <div class="brand-stack">
                <span class="brand-kicker">SmartPresence</span>
                <h1 class="brand-title">Registrasi Wajah & RFID</h1>
            </div>
            <div class="topbar-meta">
                <span class="user-pill"><?= esc((string) (session()->get('nama') ?: session()->get('username'))) ?></span>
                <a class="btn btn-ghost" href="<?= base_url('logout') ?>">Logout</a>
            </div>
        </div>
    </header>

    <main class="app-shell">
        <div class="nav-pills">
            <a href="<?= base_url('dashboard') ?>">Dashboard</a>
            <a href="<?= base_url('siswa/data') ?>">Data Siswa</a>
            <a href="<?= base_url('guru') ?>">Data Guru</a>
            <a class="primary" href="<?= base_url('admin/registrasi') ?>">Registrasi Wajah & RFID</a>
            <a href="<?= base_url('jadwal') ?>">Jadwal</a>
        </div>

        <?php if (session()->getFlashdata('error')): ?>
            <div class="flash error"><?= esc(session()->getFlashdata('error')) ?></div>
        <?php endif; ?>

        <?php if (session()->getFlashdata('success')): ?>
            <div class="flash success"><?= esc(session()->getFlashdata('success')) ?></div>
        <?php endif; ?>

        <section class="panel">
            <h3>Status Alat & Tombol ON Regis</h3>
            <p class="page-note">Pilih alat yang sedang online untuk masuk mode registrasi. Alat online jika heartbeat diterima dalam <?= esc((string) $onlineWindowSec) ?> detik terakhir.</p>
            <div class="table-wrap" style="margin-top: 10px;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Perangkat</th>
                            <th>Status</th>
                            <th>Mode</th>
                            <th>Last Seen</th>
                            <th>Pesan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (! empty($devices)): ?>
                            <?php foreach ($devices as $device): ?>
                                <?php $isOnline = (bool) ($device['is_online'] ?? false); ?>
                                <tr>
                                    <td>
                                        <strong><?= esc((string) (($device['device_name'] ?? '') !== '' ? $device['device_name'] : $device['device_code'])) ?></strong><br>
                                        <span class="helper"><?= esc((string) ($device['device_code'] ?? '-')) ?></span>
                                    </td>
                                    <td>
                                        <span class="status-chip <?= $isOnline ? 'success' : 'danger' ?>">
                                            <?= $isOnline ? 'Tersambung' : 'Terputus' ?>
                                        </span>
                                    </td>
                                    <td><?= esc(strtoupper((string) ($device['status_mode'] ?? 'attendance'))) ?></td>
                                    <td><?= esc((string) ($device['last_seen_human'] ?? '-')) ?></td>
                                    <td><?= esc((string) (($device['last_message'] ?? '') !== '' ? $device['last_message'] : '-')) ?></td>
                                    <td>
                                        <form class="device-action-form" action="<?= base_url('admin/registrasi/mulai') ?>" method="post">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="device_id" value="<?= (int) ($device['id_device'] ?? 0) ?>">
                                            <button class="btn btn-secondary" type="submit" <?= $isOnline ? '' : 'disabled' ?>>ON Regis</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6">Belum ada alat IoT terdaftar. Jalankan service device agar data alat muncul otomatis.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="panel session-box">
            <div class="session-head">
                <h3>Status Sesi Registrasi</h3>
                <?php if ($activeSession): ?>
                    <span
                        id="session-status-chip"
                        class="status-chip <?= in_array($sessionStatus, ['captured', 'assigned'], true) ? 'success' : (in_array($sessionStatus, ['expired', 'cancelled'], true) ? 'danger' : 'warning') ?>"
                    >
                        <?= esc(strtoupper($sessionStatus !== '' ? $sessionStatus : '-')) ?>
                    </span>
                <?php endif; ?>
            </div>

            <?php if (! $activeSession): ?>
                <p class="page-note">Belum ada sesi aktif. Klik <strong>ON Regis</strong> pada alat yang online untuk memulai capture RFID dan wajah.</p>
            <?php else: ?>
                <div class="status-layout">
                    <div class="status-block">
                        <div class="status-label">ID Session</div>
                        <div class="status-value">#<?= (int) ($activeSession['id_session'] ?? 0) ?></div>
                    </div>
                    <div class="status-block">
                        <div class="status-label">Alat</div>
                        <div id="session-device-name" class="status-value">
                            <?= esc((string) (($activeSession['device']['device_name'] ?? '') !== '' ? $activeSession['device']['device_name'] : ($activeSession['device']['device_code'] ?? '-'))) ?>
                        </div>
                    </div>
                    <div class="status-block">
                        <div class="status-label">RFID Tercapture</div>
                        <div id="session-rfid-text" class="status-value"><?= esc((string) (($activeSession['captured_rfid'] ?? '') !== '' ? $activeSession['captured_rfid'] : '-')) ?></div>
                    </div>
                    <div class="status-block">
                        <div class="status-label">Captured At</div>
                        <div id="session-captured-at" class="status-value"><?= esc((string) (($activeSession['captured_at'] ?? '') !== '' ? $activeSession['captured_at'] : '-')) ?></div>
                    </div>
                </div>

                <div class="face-preview-box">
                    <div class="status-label">Preview Wajah Tercapture</div>
                    <?php if (! empty($activeSession['captured_face'])): ?>
                        <img id="session-face-preview" src="<?= esc((string) $activeSession['captured_face']) ?>" alt="Preview wajah hasil capture">
                    <?php else: ?>
                        <img id="session-face-preview" src="" alt="Preview wajah hasil capture" style="display:none;">
                        <p id="session-face-empty" class="helper">Belum ada data wajah dari alat.</p>
                    <?php endif; ?>
                </div>

                <?php if (in_array($sessionStatus, ['waiting_device', 'captured'], true)): ?>
                    <form action="<?= base_url('admin/registrasi/sesi/' . (int) $activeSession['id_session'] . '/batal') ?>" method="post" style="margin-top: 12px;">
                        <?= csrf_field() ?>
                        <button class="btn btn-muted" type="submit" onclick="return confirm('Batalkan sesi registrasi ini?')">Batalkan Sesi</button>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
        </section>

        <section class="panel form-card">
            <h3>Form Registrasi Langsung</h3>
            <p class="page-note">Untuk siswa, isi nama lengkap saat registrasi lalu data siswa baru langsung dibuat. Kelas, NIS, dan alamat bisa dilengkapi belakangan dari menu data siswa.</p>

            <form action="<?= base_url('admin/registrasi/simpan') ?>" method="post" class="form-grid" id="register-direct-form">
                <?= csrf_field() ?>
                <input type="hidden" name="registration_session_id" value="<?= $sessionId ?>">
                <input type="hidden" name="target_id" id="target_id" value="<?= $selectedTargetId > 0 ? $selectedTargetId : (int) old('target_id', 0) ?>">

                <div class="field">
                    <label for="target_type">Tipe Data Tujuan</label>
                    <select name="target_type" id="target_type" required>
                        <option value="siswa" <?= $selectedType === 'siswa' ? 'selected' : '' ?>>Siswa</option>
                        <option value="guru" <?= $selectedType === 'guru' ? 'selected' : '' ?>>Guru</option>
                    </select>
                </div>

                <div class="field target-switch <?= $selectedType === 'siswa' ? 'active' : '' ?>" id="target-siswa-wrap">
                    <label for="nama_siswa">Nama Lengkap Siswa</label>
                    <input
                        id="nama_siswa"
                        type="text"
                        name="nama_siswa"
                        value="<?= esc(old('nama_siswa', $selectedSiswaName)) ?>"
                        placeholder="Contoh: Ananda Putra Wijaya"
                    >
                    <div class="helper">Saat disimpan, sistem otomatis membuat data siswa baru dengan nama ini.</div>
                </div>

                <div class="field target-switch <?= $selectedType === 'guru' ? 'active' : '' ?>" id="target-guru-wrap">
                    <label for="target_guru">Nama Guru</label>
                    <select id="target_guru">
                        <option value="">Pilih nama guru</option>
                        <?php foreach ($guruList as $row): ?>
                            <option
                                value="<?= (int) ($row['id_guru'] ?? 0) ?>"
                                <?= $selectedType === 'guru' && $selectedTargetId === (int) ($row['id_guru'] ?? 0) ? 'selected' : '' ?>
                            >
                                <?= esc((string) ($row['nama'] ?? '-')) ?>
                                <?= ! empty($row['nip']) ? ' (' . esc((string) $row['nip']) . ')' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field">
                    <label for="id_rfid">ID RFID</label>
                    <input
                        type="text"
                        id="id_rfid"
                        name="id_rfid"
                        value="<?= esc((string) $initialRfidValue) ?>"
                        placeholder="Contoh: 6A-B3-11-8F"
                    >
                    <div class="helper">Boleh kosong jika hanya memakai registrasi wajah.</div>
                </div>

                <div class="field">
                    <label for="foto_wajah">Data Foto Wajah (Data URI)</label>
                    <textarea
                        id="foto_wajah"
                        name="foto_wajah"
                        rows="4"
                        placeholder="Akan terisi otomatis dari hasil capture alat"
                    ><?= esc((string) $initialFaceValue) ?></textarea>
                    <div class="helper">Boleh kosong jika hanya memakai RFID. Minimal salah satu (RFID atau wajah) wajib diisi.</div>
                </div>

                <div class="field">
                    <label>Preview Wajah Untuk Disimpan</label>
                    <img id="face-preview" src="" alt="Preview wajah untuk disimpan" style="display:none;">
                    <div id="face-preview-empty" class="helper">Belum ada preview wajah.</div>
                </div>

                <div class="btn-group">
                    <button class="btn btn-primary" type="submit">Simpan ke Data Tujuan</button>
                    <?php if ($activeSession): ?>
                        <a class="btn btn-muted" href="<?= base_url('admin/registrasi?session=' . (int) $activeSession['id_session']) ?>">Refresh Session</a>
                    <?php else: ?>
                        <a class="btn btn-muted" href="<?= base_url('admin/registrasi') ?>">Reset Form</a>
                    <?php endif; ?>
                </div>
            </form>
        </section>
    </main>

    <script>
        const targetTypeEl = document.getElementById('target_type');
        const targetIdEl = document.getElementById('target_id');
        const targetSiswaWrap = document.getElementById('target-siswa-wrap');
        const targetGuruWrap = document.getElementById('target-guru-wrap');
        const namaSiswaEl = document.getElementById('nama_siswa');
        const targetGuruEl = document.getElementById('target_guru');
        const faceField = document.getElementById('foto_wajah');
        const facePreview = document.getElementById('face-preview');
        const facePreviewEmpty = document.getElementById('face-preview-empty');
        const rfidField = document.getElementById('id_rfid');

        function applyTargetVisibility() {
            const type = targetTypeEl.value === 'guru' ? 'guru' : 'siswa';
            targetSiswaWrap.classList.toggle('active', type === 'siswa');
            targetGuruWrap.classList.toggle('active', type === 'guru');
            if (namaSiswaEl) {
                namaSiswaEl.required = type === 'siswa';
            }
            syncTargetId();
        }

        function syncTargetId() {
            if (targetTypeEl.value === 'guru') {
                targetIdEl.value = targetGuruEl.value || '';
                return;
            }
            targetIdEl.value = '';
        }

        function syncFacePreview() {
            const raw = (faceField.value || '').trim();
            if (!raw) {
                facePreview.style.display = 'none';
                facePreview.removeAttribute('src');
                facePreviewEmpty.style.display = 'block';
                return;
            }

            facePreview.src = raw;
            facePreview.style.display = 'block';
            facePreviewEmpty.style.display = 'none';
        }

        targetTypeEl.addEventListener('change', applyTargetVisibility);
        targetGuruEl.addEventListener('change', syncTargetId);
        faceField.addEventListener('input', syncFacePreview);

        applyTargetVisibility();
        syncFacePreview();

        const sessionId = <?= $sessionId ?>;
        if (sessionId > 0) {
            const statusChip = document.getElementById('session-status-chip');
            const sessionRfidText = document.getElementById('session-rfid-text');
            const sessionCapturedAt = document.getElementById('session-captured-at');
            const sessionDeviceName = document.getElementById('session-device-name');
            const sessionFacePreview = document.getElementById('session-face-preview');
            const sessionFaceEmpty = document.getElementById('session-face-empty');
            const statusUrl = "<?= base_url('admin/registrasi/sesi') ?>/" + String(sessionId);
            let pollHandle = null;

            function decorateStatusChip(statusRaw) {
                if (!statusChip) {
                    return;
                }
                const status = (statusRaw || '').toLowerCase();
                statusChip.textContent = status ? status.toUpperCase() : '-';
                statusChip.classList.remove('success', 'warning', 'danger');
                if (status === 'captured' || status === 'assigned') {
                    statusChip.classList.add('success');
                } else if (status === 'expired' || status === 'cancelled') {
                    statusChip.classList.add('danger');
                } else {
                    statusChip.classList.add('warning');
                }
            }

            async function pollSession() {
                try {
                    const response = await fetch(statusUrl, {headers: {'Accept': 'application/json'}});
                    if (!response.ok) {
                        return;
                    }
                    const payload = await response.json();
                    if (!payload || payload.status !== 'ok' || !payload.data) {
                        return;
                    }

                    const data = payload.data;
                    const status = (data.status || '').toLowerCase();
                    decorateStatusChip(status);

                    const capturedRfid = (data.captured_rfid || '').trim();
                    const capturedFace = (data.captured_face || '').trim();

                    if (sessionRfidText) {
                        sessionRfidText.textContent = capturedRfid || '-';
                    }
                    if (sessionCapturedAt) {
                        sessionCapturedAt.textContent = (data.captured_at || '').trim() || '-';
                    }
                    if (sessionDeviceName && data.device) {
                        const dName = ((data.device.device_name || '').trim() || (data.device.device_code || '-'));
                        sessionDeviceName.textContent = dName;
                    }

                    if (capturedRfid && rfidField && !rfidField.value.trim()) {
                        rfidField.value = capturedRfid;
                    }
                    if (capturedFace && faceField) {
                        faceField.value = capturedFace;
                        syncFacePreview();
                    }

                    if (sessionFacePreview) {
                        if (capturedFace) {
                            sessionFacePreview.src = capturedFace;
                            sessionFacePreview.style.display = 'block';
                            if (sessionFaceEmpty) {
                                sessionFaceEmpty.style.display = 'none';
                            }
                        } else {
                            sessionFacePreview.style.display = 'none';
                            if (sessionFaceEmpty) {
                                sessionFaceEmpty.style.display = 'block';
                            }
                        }
                    }

                    if (['assigned', 'expired', 'cancelled'].includes(status) && pollHandle !== null) {
                        window.clearInterval(pollHandle);
                    }
                } catch (error) {
                    // Biarkan polling lanjut di interval berikutnya.
                }
            }

            pollSession();
            pollHandle = window.setInterval(pollSession, 2500);
        }
    </script>
</body>
</html>
