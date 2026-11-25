<?php
/**
 * @var \App\View\View $this
 * @var \App\Domain\Entity\User $user
 * @var list<\App\Domain\ReadModel\ForkliftOverview> $bookable
 */
?>
<section aria-labelledby="dashboard-title">
    <h1 id="dashboard-title"><?= $this->t('dashboard.greeting', ['name' => $user->firstName]) ?></h1>
    <p><?= $this->t('dashboard.last_login', ['date' => $this->dateTime($user->lastLoginAt)]) ?></p>
</section>

<section aria-labelledby="bookable-title">
    <h2 id="bookable-title"><?= $this->t('dashboard.bookable_now') ?></h2>

    <?php if ($bookable === []): ?>
        <p class="empty-state"><?= $this->t('dashboard.bookable_none') ?></p>
    <?php else: ?>
        <ul class="card-grid">
            <?php foreach ($bookable as $forklift): ?>
                <li><?= $this->partial('partials/forklift-card', ['forklift' => $forklift]) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <p><a href="/forklifts"><?= $this->t('dashboard.all_forklifts') ?></a></p>
</section>
