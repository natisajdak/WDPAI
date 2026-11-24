<?php
/** @var \App\View\View $this */

use App\Security\Ability;

$user = $this->currentUser();
?>
<header class="site-header">
    <a class="site-header__brand" href="/"><?= $this->t('app.name') ?></a>

    <?php if ($user !== null): ?>
        <nav class="site-header__nav" aria-label="<?= $this->t('nav.main_label') ?>">
            <ul>
                <li><a href="/dashboard"><?= $this->t('nav.dashboard') ?></a></li>
                <?php if ($this->can(Ability::ManageUsers)): ?>
                    <li><a href="/admin/users"><?= $this->t('nav.users') ?></a></li>
                <?php endif; ?>
            </ul>
        </nav>

        <div class="user-menu">
            <span class="user-menu__name"><?= $this->e($user->fullName()) ?></span>
            <span class="badge"><?= $this->t($user->role->labelKey()) ?></span>
            <form method="post" action="/logout" class="user-menu__logout">
                <?= $this->csrfField() ?>
                <button type="submit"><?= $this->t('nav.logout') ?></button>
            </form>
        </div>
    <?php endif; ?>

    <?= $this->partial('partials/lang-switch') ?>
</header>
