<?= view('partials/app_start', [
    'title' => 'Data Siswa',
    'activeNav' => 'siswa',
]) ?>
<style>
.face-pair { display: grid; grid-template-columns: 72px 72px 72px; gap: 8px; align-items: start; }
.face-cell { display: grid; gap: 4px; justify-items: center; }
.face-label { font-size: 10px; color: #5f6b7a; line-height: 1; }
.face-status { margin-top: 4px; display: block; font-size: 11px; color: #5f6b7a; line-height: 1.2; }
.face-vector-placeholder {
    width: 64px;
    height: 64px;
    border: 1px dashed #cdd5df;
    color: #8a95a5;
    font-size: 10px;
    display: grid;
    place-items: center;
}
.face-thumb { cursor: zoom-in; }
.image-modal {
    position: fixed;
    inset: 0;
    background: rgba(10, 13, 18, 0.72);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    padding: 20px;
}
.image-modal.open { display: flex; }
.image-modal-dialog {
    position: relative;
    max-width: min(92vw, 980px);
    max-height: 88vh;
}
.image-modal-image {
    display: block;
    max-width: 100%;
    max-height: 88vh;
    border-radius: 8px;
    background: #0b0f15;
    object-fit: contain;
}
.image-modal-close {
    position: absolute;
    top: -14px;
    right: -14px;
    width: 32px;
    height: 32px;
    border: 0;
    border-radius: 16px;
    background: #fff;
    color: #1f2a36;
    font-size: 20px;
    line-height: 1;
    cursor: pointer;
}
</style>

<div class="page-toolbar">
    <div class="page-toolbar-title">
        <h2>Data Siswa</h2>
        <p>Kelola profil siswa, kelas, RFID, dan data wajah.</p>
    </div>
    <a class="btn btn-primary" href="<?= base_url('siswa/tambah') ?>">Tambah Siswa</a>
</div>

<section class="panel">
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Wajah</th>
                    <th>Nama</th>
                    <th>No Induk</th>
                    <th>Kelas</th>
                    <th>Alamat</th>
                    <th>RFID</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (! empty($siswa)): ?>
                    <?php $no = 1; ?>
                    <?php foreach ($siswa as $row): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td>
                                <?php if (! empty($row['foto_wajah'])): ?>
                                    <div class="face-pair" data-landmark-url="<?= base_url('siswa/landmark/' . (int) $row['id']) ?>">
                                        <div class="face-cell">
                                            <span class="face-label">Real</span>
                                            <img class="face-thumb js-real-img" src="<?= esc($row['foto_wajah']) ?>" alt="Wajah Siswa Real">
                                        </div>
                                        <div class="face-cell">
                                            <span class="face-label">Vector</span>
                                            <img class="face-thumb js-vector-img" src="" alt="Wajah Siswa Vector" style="display:none">
                                            <div class="face-vector-placeholder js-vector-placeholder">N/A</div>
                                        </div>
                                        <div class="face-cell">
                                            <span class="face-label">Overlay</span>
                                            <img class="face-thumb js-overlay-img" src="" alt="Wajah Siswa Overlay" style="display:none">
                                            <div class="face-vector-placeholder js-overlay-placeholder">N/A</div>
                                        </div>
                                    </div>
                                    <small class="face-status js-face-status">Memuat landmark...</small>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td><?= esc($row['nama']) ?></td>
                            <td><?= esc((string) (($row['no_induk'] ?? '') !== '' ? $row['no_induk'] : '-')) ?></td>
                            <td><?= esc((string) (($row['kelas'] ?? '') !== '' ? $row['kelas'] : '-')) ?></td>
                            <td><?= esc((string) (($row['alamat'] ?? '') !== '' ? $row['alamat'] : '-')) ?></td>
                            <td><?= esc($row['id_rfid'] ?? '-') ?></td>
                            <td>
                                <div class="actions">
                                    <a href="<?= base_url('siswa/edit/' . $row['id']) ?>">Edit</a>
                                    <a href="<?= base_url('admin/registrasi?target_type=siswa&target_id=' . $row['id'] . '&nama_siswa=' . urlencode((string) $row['nama'])) ?>">Registrasi</a>
                                    <a class="danger" href="<?= base_url('siswa/hapus/' . $row['id']) ?>" onclick="return confirm('Yakin hapus data siswa ini?')">Hapus</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8">Belum ada data siswa.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<div class="image-modal" id="imageModal" aria-hidden="true">
    <div class="image-modal-dialog">
        <button class="image-modal-close" type="button" id="imageModalClose" aria-label="Tutup">×</button>
        <img class="image-modal-image" id="imageModalImg" src="" alt="Preview wajah">
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const wrappers = document.querySelectorAll('.face-pair');
    wrappers.forEach(async (wrapper) => {
        const vectorImg = wrapper.querySelector('.js-vector-img');
        const vectorPlaceholder = wrapper.querySelector('.js-vector-placeholder');
        const overlayImg = wrapper.querySelector('.js-overlay-img');
        const overlayPlaceholder = wrapper.querySelector('.js-overlay-placeholder');
        const statusEl = wrapper.parentElement.querySelector('.js-face-status');
        const endpoint = wrapper.getAttribute('data-landmark-url');
        if (!vectorImg || !vectorPlaceholder || !overlayImg || !overlayPlaceholder || !statusEl || !endpoint) return;

        try {
            const response = await fetch(endpoint, { headers: { 'Accept': 'application/json' } });
            const payload = await response.json();
            const status = String(payload?.status || '');
            const landmarkCount = Number(payload?.data?.landmark_count || 0);
            const landmarkStatus = String(payload?.data?.landmark_status || '');
            const vectorImageUrl = String(payload?.data?.vector_image_url || '');
            const overlayImageUrl = String(payload?.data?.overlay_image_url || '');
            if (status === 'error') {
                statusEl.textContent = 'Gagal ambil landmark';
                return;
            }
            if (landmarkStatus === 'invalid') {
                statusEl.textContent = 'Landmark tidak valid';
                return;
            }
            if (vectorImageUrl === '' || overlayImageUrl === '') {
                statusEl.textContent = 'Belum ada landmark';
                return;
            }
            vectorImg.src = vectorImageUrl;
            vectorImg.style.display = 'block';
            vectorPlaceholder.style.display = 'none';
            overlayImg.src = overlayImageUrl;
            overlayImg.style.display = 'block';
            overlayPlaceholder.style.display = 'none';
            statusEl.textContent = `${landmarkCount} titik`;
        } catch (e) {
            statusEl.textContent = 'Gagal ambil landmark';
        }
    });

    const modal = document.getElementById('imageModal');
    const modalImg = document.getElementById('imageModalImg');
    const modalClose = document.getElementById('imageModalClose');
    if (!modal || !modalImg || !modalClose) return;

    document.addEventListener('click', (event) => {
        const target = event.target;
        if (!(target instanceof HTMLImageElement)) return;
        if (!target.classList.contains('face-thumb')) return;
        if (!target.src) return;
        modalImg.src = target.src;
        modal.classList.add('open');
        modal.setAttribute('aria-hidden', 'false');
    });

    const closeModal = () => {
        modal.classList.remove('open');
        modal.setAttribute('aria-hidden', 'true');
        modalImg.src = '';
    };

    modalClose.addEventListener('click', closeModal);
    modal.addEventListener('click', (event) => {
        if (event.target === modal) closeModal();
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && modal.classList.contains('open')) {
            closeModal();
        }
    });
});
</script>

<?= view('partials/app_end') ?>
