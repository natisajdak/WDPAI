<?php
/**
 * @var \App\View\View $this
 * @var string $content
 * @var string|null $title
 */
?>
<!doctype html>
<html lang="<?= $this->e($this->locale()) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= $this->csrfToken() ?>">
    <title><?= isset($title) ? $this->e($title) . ' · ' : '' ?><?= $this->t('app.name') ?></title>
</head>
<body>
    <a class="skip-link" href="#main"><?= $this->t('nav.skip_to_content') ?></a>

    <?= $this->partial('partials/header') ?>

    <main id="main" tabindex="-1">
        <?= $content ?>
    </main>

    <?= $this->partial('partials/footer') ?>
</body>
</html>
