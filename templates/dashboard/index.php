<?php
/**
 * @var \App\View\View $this
 * @var \App\Domain\Entity\User $user
 */

use App\Security\Ability;
?>
<section aria-labelledby="dashboard-title">
    <h1 id="dashboard-title"><?= $this->t('dashboard.greeting', ['name' => $user->firstName]) ?></h1>
    <p><?= $this->t('dashboard.role', ['role' => $this->t($user->role->labelKey())]) ?></p>
    <p><?= $this->t('dashboard.last_login', ['date' => $this->dateTime($user->lastLoginAt)]) ?></p>

    <?php if ($this->can(Ability::ManageUsers)): ?>
        <p><a href="/admin/users"><?= $this->t('nav.users') ?></a></p>
    <?php endif; ?>
</section>
