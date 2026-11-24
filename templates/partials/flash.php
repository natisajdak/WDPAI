<?php
/** @var \App\View\View $this */
?>
<?php foreach ($this->flashes() as $type => $message): ?>
    <div class="alert alert--<?= $this->e($type) ?>" role="<?= $type === 'error' ? 'alert' : 'status' ?>">
        <?= $this->e($message) ?>
    </div>
<?php endforeach; ?>
