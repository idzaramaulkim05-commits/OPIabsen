<?php if (session()->getFlashdata('error')): ?>
    <div class="flash error" role="alert"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>

<?php if (session()->getFlashdata('success')): ?>
    <div class="flash success" role="status"><?= esc(session()->getFlashdata('success')) ?></div>
<?php endif; ?>
