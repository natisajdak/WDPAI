<?php
/**
 * @var \App\View\View $this
 * @var \App\Domain\ReadModel\ForkliftOverview $forklift
 * @var list<\App\Domain\Entity\TechnicalInspection> $inspections
 */
?>
<article class="forklift-details" aria-labelledby="forklift-title">
    <p><a href="/forklifts"><?= $this->t('forklifts.back_to_list') ?></a></p>

    <header class="forklift-details__header">
        <h1 id="forklift-title"><?= $this->e($forklift->internalCode) ?></h1>
        <p class="forklift-details__model"><?= $this->e($forklift->manufacturer . ' ' . $forklift->modelName) ?></p>
        <?= $this->partial('partials/forklift-badges', ['forklift' => $forklift]) ?>
    </header>

    <?php if (!$forklift->isBookable()): ?>
        <div class="alert alert--warning" role="status"><?= $this->t('forklift.not_bookable') ?></div>
    <?php endif; ?>

    <section aria-labelledby="specs-title">
        <h2 id="specs-title"><?= $this->t('forklift.specs') ?></h2>
        <dl class="details-list">
            <div><dt><?= $this->t('forklift.serial_number') ?></dt><dd><?= $this->e($forklift->serialNumber) ?></dd></div>
            <div><dt><?= $this->t('forklift.production_year') ?></dt><dd><?= $this->e($forklift->productionYear) ?></dd></div>
            <div><dt><?= $this->t('forklift.fuel') ?></dt><dd><?= $this->t($forklift->fuelType->labelKey()) ?></dd></div>
            <div><dt><?= $this->t('forklift.max_load') ?></dt><dd><?= $this->number($forklift->maxLoadKg) ?> kg</dd></div>
            <div><dt><?= $this->t('forklift.max_lift_height') ?></dt><dd><?= $this->number($forklift->maxLiftHeightMm) ?> mm</dd></div>
            <div>
                <dt><?= $this->t('forklift.warehouse') ?></dt>
                <dd><?= $forklift->warehouseName === null ? $this->t('forklift.no_warehouse') : $this->e($forklift->warehouseName) ?></dd>
            </div>
            <div><dt><?= $this->t('forklift.open_faults_label') ?></dt><dd><?= $this->e($forklift->openFaultsCount) ?></dd></div>
        </dl>
    </section>

    <section aria-labelledby="udt-title">
        <h2 id="udt-title"><?= $this->t('forklift.udt_history') ?></h2>

        <?php if ($inspections === []): ?>
            <p class="empty-state"><?= $this->t('forklift.udt_none') ?></p>
        <?php else: ?>
            <table class="data-table">
                <caption class="visually-hidden"><?= $this->t('forklift.udt_history') ?></caption>
                <thead>
                    <tr>
                        <th scope="col"><?= $this->t('forklift.udt.protocol') ?></th>
                        <th scope="col"><?= $this->t('forklift.udt.date') ?></th>
                        <th scope="col"><?= $this->t('forklift.udt.valid_until') ?></th>
                        <th scope="col"><?= $this->t('forklift.udt.notes') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($inspections as $inspection): ?>
                        <tr>
                            <th scope="row"><?= $this->e($inspection->protocolNumber) ?></th>
                            <td><?= $this->date($inspection->inspectionDate) ?></td>
                            <td><?= $this->date($inspection->validUntil) ?></td>
                            <td><?= $this->e($inspection->notes ?? '–') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>
</article>
