<?php
/**
 * @var \App\View\View $this
 * @var string $titleKey
 * @var int $status
 * @var string $message
 * @var string|null $details tylko w trybie debug
 */
?>
<section class="error-page" aria-labelledby="error-title">
    <p class="error-page__status"><?= $this->e($status) ?></p>
    <h1 id="error-title"><?= $this->t($titleKey) ?></h1>
    <p><?= $this->e($message) ?></p>

    <?php if ($details !== null): ?>
        <pre class="error-page__details"><?= $this->e($details) ?></pre>
    <?php endif; ?>

    <p><a href="/"><?= $this->t('error.back_home') ?></a></p>
</section>
