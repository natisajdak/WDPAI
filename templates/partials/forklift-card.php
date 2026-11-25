<?php
/**
 * @var \App\View\View $this
 * @var \App\Domain\ReadModel\ForkliftOverview $forklift
 */
?>
<article class="forklift-card" aria-labelledby="forklift-<?= $this->e($forklift->id) ?>-title">
    <h3 id="forklift-<?= $this->e($forklift->id) ?>-title" class="forklift-card__title">
        <a href="<?= $this->url('/forklifts/' . $forklift->id) ?>"><?= $this->e($forklift->internalCode) ?></a>
    </h3>
    <p class="forklift-card__model"><?= $this->e($forklift->manufacturer . ' ' . $forklift->modelName) ?></p>

    <?= $this->partial('partials/forklift-badges', ['forklift' => $forklift]) ?>

    <dl class="forklift-card__specs">
        <div>
            <dt><?= $this->t('forklift.max_load') ?></dt>
            <dd><?= $this->number($forklift->maxLoadKg) ?> kg</dd>
        </div>
        <div>
            <dt><?= $this->t('forklift.fuel') ?></dt>
            <dd><?= $this->t($forklift->fuelType->labelKey()) ?></dd>
        </div>
        <div>
            <dt><?= $this->t('forklift.warehouse') ?></dt>
            <dd><?= $forklift->warehouseName === null ? $this->t('forklift.no_warehouse') : $this->e($forklift->warehouseName) ?></dd>
        </div>
        <div>
            <dt><?= $this->t('forklift.udt_valid_until') ?></dt>
            <dd><?= $this->date($forklift->udtValidUntil) ?></dd>
        </div>
    </dl>
</article>
