<?php
$rawShifts = $jadwal['shifts'] ?? [];
if (is_string($rawShifts)) {
    $decoded = json_decode($rawShifts, true);
    $rawShifts = is_array($decoded) ? $decoded : [];
}
$shifts = is_array($rawShifts) && $rawShifts !== []
    ? array_values($rawShifts)
    : [[
        'nama' => 'Jadwal 1',
        'masuk_awal' => '',
        'masuk_akhir' => '',
        'pulang_awal' => '',
        'pulang_akhir' => '',
    ]];
$rawHari = old('hari', $jadwal['hari_list'] ?? ($jadwal['hari'] ?? []));
if (is_string($rawHari)) {
    $rawHari = preg_split('/\s*,\s*/', $rawHari, -1, PREG_SPLIT_NO_EMPTY) ?: [];
}
$selectedHari = is_array($rawHari) ? array_values($rawHari) : [];
?>
<?= view('partials/app_start', [
    'title' => (string) ($title ?? 'Form Jadwal'),
    'activeNav' => 'jadwal',
]) ?>

<section class="panel form-card">
    <h3><?= esc($title ?? 'Form Jadwal') ?></h3>
    <form action="<?= esc($action) ?>" method="post" class="form-grid" id="jadwalForm">
        <div class="info-box">
            <strong>Info:</strong> Tetapkan rentang waktu masuk dan pulang untuk setiap hari. Satu hari dapat memiliki lebih dari satu jadwal.
        </div>

        <div class="full-span">
            <div class="field-label">Hari</div>
            <div class="checkbox-grid">
                <?php foreach ($hariList as $hari): ?>
                    <div class="checkbox-row">
                        <input id="hari_<?= esc($hari) ?>" type="checkbox" name="hari[]" value="<?= esc($hari) ?>" <?= in_array($hari, $selectedHari, true) ? 'checked' : '' ?>>
                        <label for="hari_<?= esc($hari) ?>"><?= esc($hari) ?></label>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="full-span">
            <div class="field-label">Jadwal Masuk & Keluar</div>
            <div class="shift-panel" id="shiftsContainer">
                <?php foreach ($shifts as $index => $shift): ?>
                    <div class="shift-item" data-shift-index="<?= (int) $index ?>">
                        <div class="shift-item-header">
                            <span class="shift-title"><?= esc($shift['nama'] ?? 'Jadwal ' . ($index + 1)) ?></span>
                            <button type="button" class="btn btn-danger shift-item-remove" onclick="removeShift(this)">Hapus Jadwal</button>
                        </div>
                        <div class="shift-grid">
                            <div class="shift-group">
                                <div class="shift-group-title">Masuk</div>
                                <div class="field">
                                    <label>Nama Jadwal</label>
                                    <input data-field="nama" type="text" name="shift_<?= (int) $index ?>_nama" value="<?= esc($shift['nama'] ?? '') ?>" placeholder="Contoh: Jadwal Pagi">
                                </div>
                                <div class="time-group">
                                    <div class="field">
                                        <label>Dari</label>
                                        <input data-field="masuk_awal" type="time" name="shift_<?= (int) $index ?>_masuk_awal" value="<?= esc(substr((string) ($shift['masuk_awal'] ?? ''), 0, 5)) ?>" required>
                                    </div>
                                    <div class="field">
                                        <label>Sampai</label>
                                        <input data-field="masuk_akhir" type="time" name="shift_<?= (int) $index ?>_masuk_akhir" value="<?= esc(substr((string) ($shift['masuk_akhir'] ?? ''), 0, 5)) ?>" required>
                                    </div>
                                </div>
                            </div>
                            <div class="shift-group">
                                <div class="shift-group-title">Pulang</div>
                                <div class="spacer-label"></div>
                                <div class="time-group">
                                    <div class="field">
                                        <label>Dari</label>
                                        <input data-field="pulang_awal" type="time" name="shift_<?= (int) $index ?>_pulang_awal" value="<?= esc(substr((string) ($shift['pulang_awal'] ?? ''), 0, 5)) ?>" required>
                                    </div>
                                    <div class="field">
                                        <label>Sampai</label>
                                        <input data-field="pulang_akhir" type="time" name="shift_<?= (int) $index ?>_pulang_akhir" value="<?= esc(substr((string) ($shift['pulang_akhir'] ?? ''), 0, 5)) ?>" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="btn-group">
                <button type="button" class="btn btn-secondary" onclick="addShift()">Tambah Jadwal</button>
            </div>
            <input type="hidden" id="shiftCount" name="shift_count" value="<?= count($shifts) ?>">
        </div>

        <div class="btn-group full-span">
            <button class="btn btn-primary" type="submit">Simpan</button>
            <a class="btn btn-muted" href="<?= base_url('jadwal') ?>">Kembali</a>
        </div>
    </form>
</section>

<script>
    function shiftTemplate(index) {
        return `
            <div class="shift-item" data-shift-index="${index}">
                <div class="shift-item-header">
                    <span class="shift-title">Jadwal ${index + 1}</span>
                    <button type="button" class="btn btn-danger shift-item-remove" onclick="removeShift(this)">Hapus Jadwal</button>
                </div>
                <div class="shift-grid">
                    <div class="shift-group">
                        <div class="shift-group-title">Masuk</div>
                        <div class="field">
                            <label>Nama Jadwal</label>
                            <input data-field="nama" type="text" name="shift_${index}_nama" placeholder="Contoh: Jadwal Siang">
                        </div>
                        <div class="time-group">
                            <div class="field">
                                <label>Dari</label>
                                <input data-field="masuk_awal" type="time" name="shift_${index}_masuk_awal" required>
                            </div>
                            <div class="field">
                                <label>Sampai</label>
                                <input data-field="masuk_akhir" type="time" name="shift_${index}_masuk_akhir" required>
                            </div>
                        </div>
                    </div>
                    <div class="shift-group">
                        <div class="shift-group-title">Pulang</div>
                        <div class="spacer-label"></div>
                        <div class="time-group">
                            <div class="field">
                                <label>Dari</label>
                                <input data-field="pulang_awal" type="time" name="shift_${index}_pulang_awal" required>
                            </div>
                            <div class="field">
                                <label>Sampai</label>
                                <input data-field="pulang_akhir" type="time" name="shift_${index}_pulang_akhir" required>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    function updateShiftCount() {
        const items = document.querySelectorAll('#shiftsContainer .shift-item');
        document.getElementById('shiftCount').value = String(items.length);
    }

    function renumberShifts() {
        document.querySelectorAll('#shiftsContainer .shift-item').forEach((item, index) => {
            item.dataset.shiftIndex = String(index);
            const title = item.querySelector('.shift-title');
            if (title) {
                const inputName = item.querySelector('[data-field="nama"]');
                title.textContent = inputName && inputName.value.trim() ? inputName.value.trim() : `Jadwal ${index + 1}`;
            }
            item.querySelectorAll('[data-field]').forEach((input) => {
                input.name = `shift_${index}_${input.dataset.field}`;
            });
        });
        updateShiftCount();
    }

    function addShift() {
        const container = document.getElementById('shiftsContainer');
        const nextIndex = container.querySelectorAll('.shift-item').length;
        container.insertAdjacentHTML('beforeend', shiftTemplate(nextIndex));
        updateShiftCount();
    }

    function removeShift(btn) {
        const container = document.getElementById('shiftsContainer');
        if (container.querySelectorAll('.shift-item').length <= 1) {
            alert('Minimal harus ada satu jadwal.');
            return;
        }
        btn.closest('.shift-item').remove();
        renumberShifts();
    }

    document.getElementById('shiftsContainer').addEventListener('input', (event) => {
        if (event.target.matches('[data-field="nama"]')) {
            renumberShifts();
        }
    });

    document.getElementById('jadwalForm').addEventListener('submit', function (event) {
        renumberShifts();
        if (document.querySelectorAll('input[name="hari[]"]:checked').length === 0) {
            event.preventDefault();
            alert('Pilih minimal satu hari jadwal.');
            return;
        }
        if (document.querySelectorAll('#shiftsContainer .shift-item').length === 0) {
            event.preventDefault();
            alert('Minimal harus ada satu jadwal.');
        }
    });
</script>

<?= view('partials/app_end') ?>
