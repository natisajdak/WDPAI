<?php
/**
 * @var \App\View\View $this
 * @var string $name
 * @var string $link
 * @var int $minutes
 */
?>
<h1><?= $this->t('mail.password_reset.heading', ['name' => $name]) ?></h1>
<p><?= $this->t('mail.password_reset.intro') ?></p>
<p><a class="button" href="<?= $this->e($link) ?>"><?= $this->t('mail.password_reset.button') ?></a></p>
<p class="muted"><?= $this->t('mail.password_reset.expiry', ['minutes' => $minutes]) ?></p>
<p class="muted"><?= $this->t('mail.password_reset.ignore') ?></p>
