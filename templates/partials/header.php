<?php
/** @var \App\View\View $this */
?>
<header class="site-header">
    <a class="site-header__brand" href="/"><?= $this->t('app.name') ?></a>

    <nav class="site-header__nav" aria-label="<?= $this->t('nav.main_label') ?>">
        <ul>
            <li><a href="/"><?= $this->t('nav.home') ?></a></li>
        </ul>
    </nav>

    <?= $this->partial('partials/lang-switch') ?>
</header>
