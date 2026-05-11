<?php
$devices = is_array($devices ?? null) ? $devices : [];
$kelasList = is_array($kelasList ?? null) ? $kelasList : [];
$formValues = is_array($formValues ?? null) ? $formValues : [];
$activeSession = is_array($activeSession ?? null) ? $activeSession : null;
$selectedType = 'siswa';
$selectedTargetId = (int) ($selectedTargetId ?? 0);
$onlineWindowSec = (int) ($onlineWindowSec ?? 45);

$initialRfidValue = old('id_rfid', (string) ($initialRfid ?? ($formValues['id_rfid'] ?? '')));
$initialFaceValue = old('foto_wajah', (string) ($initialFace ?? ($formValues['foto_wajah'] ?? '')));
$namaValue = old('nama', (string) ($formValues['nama'] ?? ''));
$noIndukValue = old('no_induk', (string) ($formValues['no_induk'] ?? ''));
$kelasValue = old('kelas', (string) ($formValues['kelas'] ?? ''));
$kelasBaruValue = old('kelas_baru', '');
$alamatValue = old('alamat', (string) ($formValues['alamat'] ?? ''));
$nipValue = old('nip', (string) ($formValues['nip'] ?? ''));
$usernameValue = old('username', (string) ($formValues['username'] ?? ''));
$kelasWaliValue = old('kelas_wali', (string) ($formValues['kelas_wali'] ?? ''));
$isWaliValue = (int) old('is_wali_kelas', $kelasWaliValue !== '' ? 1 : (int) ($formValues['is_wali_kelas'] ?? 0));
$kelasWaliBaruValue = old('kelas_wali_baru', '');
$sessionId = (int) ($activeSession['id_session'] ?? 0);
$sessionStatus = strtolower((string) ($activeSession['status'] ?? ''));
?>
<?= view('partials/app_start', [
    'title' => 'Registrasi Wajah & RFID',
    'activeNav' => 'registrasi',
]) ?>

<div class="page-toolbar">
    <div class="page-toolbar-title">
        <h2>Registrasi Wajah & RFID</h2>
        <p>Pilih alat online, ambil data RFID/wajah, lalu simpan ke siswa.</p>
    </div>
    <a class="btn btn-muted" href="<?= base_url('admin/registrasi/pemetaan') ?>">Pemetaan Registrasi</a>
</div>

<section class="panel">
    <h3>Status Alat & Tombol ON Regis</h3>
    <p class="page-note">Alat online jika heartbeat diterima dalam <?= esc((string) $onlineWindowSec) ?> detik terakhir.</p>
    <div class="table-wrap with-space">
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
                <?php if ($devices !== []): ?>
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
                <p id="session-face-empty" class="helper" hidden>Belum ada data wajah dari alat.</p>
            <?php else: ?>
                <img id="session-face-preview" src="" alt="Preview wajah hasil capture" hidden>
                <p id="session-face-empty" class="helper">Belum ada data wajah dari alat.</p>
            <?php endif; ?>
        </div>

        <?php if (in_array($sessionStatus, ['waiting_device', 'captured'], true)): ?>
            <form id="close-regis-form" action="<?= base_url('admin/registrasi/sesi/' . (int) $activeSession['id_session'] . '/batal') ?>" method="post" class="btn-group">
                <?= csrf_field() ?>
                <button class="btn btn-muted" type="submit" onclick="return confirm('Tutup mode registrasi untuk device ini dan kembali ke mode absen?')">Close Regis</button>
            </form>
        <?php endif; ?>
    <?php endif; ?>
</section>

<section class="panel form-card">
    <h3>Form Registrasi Langsung</h3>
    <p class="page-note">Isi data siswa secara lengkap. Jika ada sesi capture aktif, RFID/wajah akan otomatis terisi.</p>

    <form action="<?= base_url('admin/registrasi/simpan') ?>" method="post" class="form-grid" id="register-direct-form">
        <?= csrf_field() ?>
        <input type="hidden" name="registration_session_id" value="<?= $sessionId ?>">
        <input type="hidden" name="target_id" id="target_id" value="<?= $selectedTargetId > 0 ? $selectedTargetId : (int) old('target_id', 0) ?>">

        <div class="field">
            <label for="target_type">Mode Registrasi</label>
            <select name="target_type" id="target_type" required>
                <option value="siswa" selected>Siswa</option>
            </select>
        </div>

        <div class="field">
            <label for="nama">Nama Lengkap</label>
            <input
                id="nama"
                type="text"
                name="nama"
                value="<?= esc((string) $namaValue) ?>"
                placeholder="Contoh: Ananda Putra Wijaya"
                required
            >
        </div>

        <div class="field target-switch <?= $selectedType === 'siswa' ? 'active' : '' ?>" data-target="siswa">
            <label for="no_induk">No Induk</label>
            <input
                id="no_induk"
                type="text"
                name="no_induk"
                value="<?= esc((string) $noIndukValue) ?>"
                placeholder="Opsional (NIS/NISN)"
            >
        </div>

        <div class="field target-switch <?= $selectedType === 'siswa' ? 'active' : '' ?>" data-target="siswa">
            <label for="kelas">Kelas</label>
            <select id="kelas" name="kelas">
                <option value="">Pilih kelas</option>
                <?php foreach ($kelasList as $kelas): ?>
                    <option value="<?= esc((string) $kelas) ?>" <?= (string) $kelasValue === (string) $kelas ? 'selected' : '' ?>>
                        <?= esc((string) $kelas) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <div class="helper">Pilih kelas yang sudah tersedia di master kelas.</div>
        </div>

        <div class="field target-switch <?= $selectedType === 'siswa' ? 'active' : '' ?>" data-target="siswa">
            <label for="kelas_baru">Kelas Baru (Jika Belum Ada)</label>
            <input
                id="kelas_baru"
                type="text"
                name="kelas_baru"
                value="<?= esc((string) $kelasBaruValue) ?>"
                placeholder="Contoh: X-IPA-1"
            >
            <div class="helper">Isi jika kelas belum tersedia di daftar.</div>
        </div>

        <div class="field target-switch <?= $selectedType === 'siswa' ? 'active' : '' ?>" data-target="siswa">
            <label for="alamat">Alamat</label>
            <textarea id="alamat" name="alamat" rows="2" placeholder="Opsional"><?= esc((string) $alamatValue) ?></textarea>
        </div>

        <div class="field target-switch <?= $selectedType === 'guru' ? 'active' : '' ?>" data-target="guru">
            <label for="nip">NIP</label>
            <input
                id="nip"
                type="text"
                name="nip"
                value="<?= esc((string) $nipValue) ?>"
                placeholder="Contoh: 199001011990031001"
                required
            >
        </div>

        <div class="field target-switch <?= $selectedType === 'guru' ? 'active' : '' ?>" data-target="guru">
            <label for="username">Username Login Guru</label>
            <input
                id="username"
                type="text"
                name="username"
                value="<?= esc((string) $usernameValue) ?>"
                placeholder="Contoh: guru.wali1"
                required
            >
        </div>

        <div class="field target-switch <?= $selectedType === 'guru' ? 'active' : '' ?>" data-target="guru">
            <label for="password">Password Guru</label>
            <input
                id="password"
                type="password"
                name="password"
                placeholder="Wajib diisi untuk guru baru"
            >
        </div>

        <div class="checkbox-row target-switch <?= $selectedType === 'guru' ? 'active' : '' ?>" data-target="guru">
            <input id="is_wali_kelas" type="checkbox" name="is_wali_kelas" value="1" <?= $isWaliValue === 1 ? 'checked' : '' ?>>
            <label for="is_wali_kelas">Jadikan Wali Kelas</label>
        </div>

        <div class="field target-switch <?= $selectedType === 'guru' ? 'active' : '' ?>" data-target="guru">
            <label for="kelas_wali">Kelas Wali</label>
            <select id="kelas_wali" name="kelas_wali">
                <option value="">Pilih kelas wali</option>
                <?php foreach ($kelasList as $kelas): ?>
                    <option value="<?= esc((string) $kelas) ?>" <?= (string) $kelasWaliValue === (string) $kelas ? 'selected' : '' ?>>
                        <?= esc((string) $kelas) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="field target-switch <?= $selectedType === 'guru' ? 'active' : '' ?>" data-target="guru">
            <label for="kelas_wali_baru">Kelas Wali Baru (Jika Belum Ada)</label>
            <input
                id="kelas_wali_baru"
                type="text"
                name="kelas_wali_baru"
                value="<?= esc((string) $kelasWaliBaruValue) ?>"
                placeholder="Contoh: XI-IPS-2"
            >
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
            <div class="helper">Boleh dikosongkan jika belum ada kartu RFID.</div>
        </div>

        <div class="field">
            <label for="foto_wajah">Data Foto Wajah (Data URI)</label>
            <textarea
                id="foto_wajah"
                name="foto_wajah"
                rows="4"
                placeholder="Akan terisi otomatis dari hasil capture alat"
            ><?= esc((string) $initialFaceValue) ?></textarea>
            <div class="helper">Boleh dikosongkan jika belum ada data wajah.</div>
        </div>

        <div class="field">
            <label>Preview Wajah Untuk Disimpan</label>
            <img id="face-preview" src="" alt="Preview wajah untuk disimpan" hidden>
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

<script>
    const targetTypeEl = document.getElementById('target_type');
    const siswaFields = document.querySelectorAll('[data-target="siswa"]');
    const guruFields = document.querySelectorAll('[data-target="guru"]');
    const isWaliCheckbox = document.getElementById('is_wali_kelas');
    const kelasWaliSelect = document.getElementById('kelas_wali');
    const kelasWaliBaruInput = document.getElementById('kelas_wali_baru');
    const faceField = document.getElementById('foto_wajah');
    const facePreview = document.getElementById('face-preview');
    const facePreviewEmpty = document.getElementById('face-preview-empty');
    const rfidField = document.getElementById('id_rfid');
    const registerFormEl = document.getElementById('register-direct-form');

    function setGroupState(nodes, isActive) {
        nodes.forEach((node) => {
            node.classList.toggle('active', isActive);
            node.querySelectorAll('input, select, textarea').forEach((control) => {
                control.disabled = !isActive;
            });
        });
    }

    function syncWaliRequirement() {
        if (!isWaliCheckbox) {
            return;
        }

        const guruActive = targetTypeEl.value === 'guru';
        if (!guruActive) {
            isWaliCheckbox.checked = false;
        }

        const isWali = guruActive && isWaliCheckbox.checked;
        if (kelasWaliSelect) {
            const hasNew = kelasWaliBaruInput && kelasWaliBaruInput.value.trim() !== '';
            kelasWaliSelect.required = isWali && !hasNew;
        }
        if (kelasWaliBaruInput) {
            const hasExisting = kelasWaliSelect && kelasWaliSelect.value !== '';
            kelasWaliBaruInput.required = isWali && !hasExisting;
        }

        if (!isWali) {
            if (kelasWaliSelect) {
                kelasWaliSelect.value = '';
            }
            if (kelasWaliBaruInput) {
                kelasWaliBaruInput.value = '';
            }
        }
    }

    function syncWaliCheckboxFromInputs() {
        if (!isWaliCheckbox || targetTypeEl.value !== 'guru') {
            return;
        }

        const hasExisting = kelasWaliSelect && kelasWaliSelect.value !== '';
        const hasNew = kelasWaliBaruInput && kelasWaliBaruInput.value.trim() !== '';
        if (hasExisting || hasNew) {
            isWaliCheckbox.checked = true;
        }
        syncWaliRequirement();
    }

    function applyTargetVisibility() {
        const type = targetTypeEl.value === 'guru' ? 'guru' : 'siswa';
        setGroupState(siswaFields, type === 'siswa');
        setGroupState(guruFields, type === 'guru');
        syncWaliRequirement();
    }

    function syncFacePreview() {
        const raw = (faceField.value || '').trim();
        if (!raw) {
            facePreview.hidden = true;
            facePreview.removeAttribute('src');
            facePreviewEmpty.hidden = false;
            return;
        }

        facePreview.src = raw;
        facePreview.hidden = false;
        facePreviewEmpty.hidden = true;
    }

    targetTypeEl.addEventListener('change', applyTargetVisibility);
    if (isWaliCheckbox) {
        isWaliCheckbox.addEventListener('change', syncWaliRequirement);
    }
    if (kelasWaliSelect) {
        kelasWaliSelect.addEventListener('change', syncWaliCheckboxFromInputs);
    }
    if (kelasWaliBaruInput) {
        kelasWaliBaruInput.addEventListener('input', syncWaliCheckboxFromInputs);
    }
    faceField.addEventListener('input', syncFacePreview);

    applyTargetVisibility();
    syncFacePreview();

    const sessionId = <?= $sessionId ?>;
    const autoCloseStatuses = new Set(['waiting_device', 'captured']);
    let latestSessionStatus = <?= json_encode($sessionStatus) ?>;
    let autoCloseSent = false;
    let skipAutoClose = false;

    if (registerFormEl) {
        registerFormEl.addEventListener('submit', () => {
            skipAutoClose = true;
        });
    }

    const closeRegisForm = document.getElementById('close-regis-form');
    if (closeRegisForm) {
        closeRegisForm.addEventListener('submit', () => {
            skipAutoClose = true;
        });
    }

    function closeRegisterSessionOnExit(reason) {
        if (skipAutoClose || autoCloseSent || sessionId <= 0) {
            return;
        }
        if (!autoCloseStatuses.has((latestSessionStatus || '').toLowerCase())) {
            return;
        }

        autoCloseSent = true;
        const closeUrl = "<?= base_url('admin/registrasi/sesi') ?>/" + String(sessionId) + "/batal";
        const payload = new URLSearchParams();
        payload.set('auto_close', '1');
        payload.set('reason', reason || 'leave_page');

        if (navigator.sendBeacon) {
            const sent = navigator.sendBeacon(closeUrl, payload);
            if (sent) {
                return;
            }
        }

        fetch(closeUrl, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: payload.toString(),
            keepalive: true,
            credentials: 'same-origin',
        }).catch(() => {});
    }

    window.addEventListener('beforeunload', () => closeRegisterSessionOnExit('beforeunload'));
    window.addEventListener('pagehide', () => closeRegisterSessionOnExit('pagehide'));

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
                latestSessionStatus = status;
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
                    sessionDeviceName.textContent = ((data.device.device_name || '').trim() || (data.device.device_code || '-'));
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
                        sessionFacePreview.hidden = false;
                        if (sessionFaceEmpty) {
                            sessionFaceEmpty.hidden = true;
                        }
                    } else {
                        sessionFacePreview.hidden = true;
                        if (sessionFaceEmpty) {
                            sessionFaceEmpty.hidden = false;
                        }
                    }
                }

                if (['assigned', 'expired', 'cancelled'].includes(status) && pollHandle !== null) {
                    window.clearInterval(pollHandle);
                }
            } catch (error) {
                // Polling lanjut pada interval berikutnya.
            }
        }

        pollSession();
        pollHandle = window.setInterval(pollSession, 2500);
    }
</script>

<?= view('partials/app_end') ?>
