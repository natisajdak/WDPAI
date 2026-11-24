<?php
/**
 * @var \App\View\View $this
 * @var list<\App\Domain\Entity\User> $users
 */
?>
<section aria-labelledby="users-title">
    <h1 id="users-title"><?= $this->t('admin.users.title') ?></h1>

    <table class="data-table">
        <caption><?= $this->t('admin.users.caption', ['count' => count($users)]) ?></caption>
        <thead>
            <tr>
                <th scope="col"><?= $this->t('admin.users.col.name') ?></th>
                <th scope="col"><?= $this->t('admin.users.col.email') ?></th>
                <th scope="col"><?= $this->t('admin.users.col.role') ?></th>
                <th scope="col"><?= $this->t('admin.users.col.locale') ?></th>
                <th scope="col"><?= $this->t('admin.users.col.status') ?></th>
                <th scope="col"><?= $this->t('admin.users.col.last_login') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
                <tr>
                    <th scope="row"><?= $this->e($user->fullName()) ?></th>
                    <td><?= $this->e($user->email) ?></td>
                    <td><?= $this->t($user->role->labelKey()) ?></td>
                    <td><?= $this->t('lang.' . $user->locale) ?></td>
                    <td><?= $this->t($user->isActive ? 'admin.users.active' : 'admin.users.inactive') ?></td>
                    <td><?= $this->dateTime($user->lastLoginAt) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>
