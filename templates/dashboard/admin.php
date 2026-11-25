<?php
/**
 * @var \App\View\View $this
 * @var \App\Domain\Entity\User $user
 * @var \App\Domain\ReadModel\FleetStats $stats
 * @var list<\App\Domain\ReadModel\ForkliftOverview> $udtWarnings
 * @var list<\App\Domain\ReadModel\ForkliftOverview> $outOfService
 * @var int $udtWarningDays
 */

$tiles = [
    ['key' => 'dashboard.stats.available', 'value' => $stats->available, 'link' => '/forklifts?status=AVAILABLE'],
    ['key' => 'dashboard.stats.in_use', 'value' => $stats->inUseNow, 'link' => null],
    ['key' => 'dashboard.stats.out_of_service', 'value' => $stats->outOfService, 'link' => '/forklifts?status=OUT_OF_SERVICE'],
    ['key' => 'dashboard.stats.open_faults', 'value' => $stats->openFaults, 'link' => null],
    ['key' => 'dashboard.stats.udt_expired', 'value' => $stats->udtExpired, 'link' => '/forklifts?sort=udt'],
];
?>
<section aria-labelledby="dashboard-title">
    <h1 id="dashboard-title"><?= $this->t('dashboard.greeting', ['name' => $user->firstName]) ?></h1>
    <p><?= $this->t('dashboard.last_login', ['date' => $this->dateTime($user->lastLoginAt)]) ?></p>
</section>

<section aria-labelledby="stats-title">
    <h2 id="stats-title"><?= $this->t('dashboard.fleet_state') ?></h2>
    <ul class="stat-grid">
        <?php foreach ($tiles as $tile): ?>
            <li class="stat-tile">
                <span class="stat-tile__value"><?= $this->number($tile['value']) ?></span>
                <span class="stat-tile__label">
                    <?php if ($tile['link'] !== null): ?>
                        <a href="<?= $this->url($tile['link']) ?>"><?= $this->t($tile['key']) ?></a>
                    <?php else: ?>
                        <?= $this->t($tile['key']) ?>
                    <?php endif; ?>
                </span>
            </li>
        <?php endforeach; ?>
    </ul>
</section>

<section aria-labelledby="udt-warnings-title">
    <h2 id="udt-warnings-title"><?= $this->t('dashboard.udt_warnings', ['days' => $udtWarningDays]) ?></h2>

    <?php if ($udtWarnings === []): ?>
        <p class="empty-state"><?= $this->t('dashboard.udt_warnings_none') ?></p>
    <?php else: ?>
        <table class="data-table">
            <caption class="visually-hidden"><?= $this->t('dashboard.udt_warnings', ['days' => $udtWarningDays]) ?></caption>
            <thead>
                <tr>
                    <th scope="col"><?= $this->t('forklift.code') ?></th>
                    <th scope="col"><?= $this->t('forklift.model') ?></th>
                    <th scope="col"><?= $this->t('forklift.udt_valid_until') ?></th>
                    <th scope="col"><?= $this->t('forklift.state') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($udtWarnings as $forklift): ?>
                    <tr>
                        <th scope="row"><a href="<?= $this->url('/forklifts/' . $forklift->id) ?>"><?= $this->e($forklift->internalCode) ?></a></th>
                        <td><?= $this->e($forklift->manufacturer . ' ' . $forklift->modelName) ?></td>
                        <td><?= $this->date($forklift->udtValidUntil) ?></td>
                        <td><?= $this->t($forklift->isUdtValid ? 'forklift.udt_expiring' : 'forklift.udt_expired') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>

<section aria-labelledby="out-of-service-title">
    <h2 id="out-of-service-title"><?= $this->t('dashboard.out_of_service') ?></h2>

    <?php if ($outOfService === []): ?>
        <p class="empty-state"><?= $this->t('dashboard.out_of_service_none') ?></p>
    <?php else: ?>
        <ul class="card-grid">
            <?php foreach ($outOfService as $forklift): ?>
                <li><?= $this->partial('partials/forklift-card', ['forklift' => $forklift]) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>
