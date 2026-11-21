<?php
/** @var \App\View\View $this */
?>
<nav class="lang-switch" aria-label="<?= $this->t('lang.switch_label') ?>">
    <ul>
        <?php foreach ($this->locales() as $locale): ?>
            <li>
                <a href="<?= $this->url('/lang/' . $locale) ?>"
                   lang="<?= $this->e($locale) ?>"
                   hreflang="<?= $this->e($locale) ?>"
                   <?= $locale === $this->locale() ? 'aria-current="true"' : '' ?>>
                    <?= $this->t('lang.' . $locale) ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</nav>
