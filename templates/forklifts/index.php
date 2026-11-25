<?php

use App\Domain\Query\ForkliftFilter;

/**
 * @var \App\View\View $this
 * @var \App\Domain\Query\ForkliftFilter $filter
 * @var \App\Domain\Query\Page<\App\Domain\ReadModel\ForkliftOverview> $page
 * @var list<\App\Domain\Entity\Warehouse> $warehouses
 * @var list<\App\Domain\Enum\ForkliftStatus> $statuses
 * @var list<\App\Domain\Enum\FuelType> $fuelTypes
 */

$statusOptions = [];
foreach ($statuses as $status) {
    $statusOptions[$status->value] = $this->translate($status->labelKey());
}

$warehouseOptions = [];
foreach ($warehouses as $warehouse) {
    $warehouseOptions[$warehouse->id] = $warehouse->name;
}

$fuelOptions = [];
foreach ($fuelTypes as $fuelType) {
    $fuelOptions[$fuelType->value] = $this->translate($fuelType->labelKey());
}

$sortOptions = [];
foreach (ForkliftFilter::SORTS as $sort) {
    $sortOptions[$sort] = $this->translate('filters.sort.' . $sort);
}
?>
<section aria-labelledby="forklifts-title">
    <h1 id="forklifts-title"><?= $this->t('forklifts.title') ?></h1>

    <form class="filters" method="get" action="/forklifts" role="search" aria-label="<?= $this->t('filters.label') ?>">
        <div class="form-field">
            <label for="field-q"><?= $this->t('filters.search') ?></label>
            <input id="field-q" name="q" type="search" maxlength="50" value="<?= $this->e($filter->search ?? '') ?>"
                   placeholder="<?= $this->t('filters.search_placeholder') ?>">
        </div>

        <?= $this->partial('components/form/select', [
            'name' => 'status',
            'labelKey' => 'filters.status',
            'emptyKey' => 'filters.status_any',
            'options' => $statusOptions,
            'selected' => $filter->status?->value,
        ]) ?>

        <?= $this->partial('components/form/select', [
            'name' => 'warehouse',
            'labelKey' => 'filters.warehouse',
            'emptyKey' => 'filters.any',
            'options' => $warehouseOptions,
            'selected' => $filter->warehouseId,
        ]) ?>

        <?= $this->partial('components/form/select', [
            'name' => 'fuel',
            'labelKey' => 'filters.fuel',
            'emptyKey' => 'filters.any',
            'options' => $fuelOptions,
            'selected' => $filter->fuelType?->value,
        ]) ?>

        <div class="form-field">
            <label for="field-min_load"><?= $this->t('filters.min_load') ?></label>
            <input id="field-min_load" name="min_load" type="number" min="0" max="100000" step="100" inputmode="numeric"
                   value="<?= $this->e($filter->minLoadKg ?? '') ?>">
        </div>

        <?= $this->partial('components/form/select', [
            'name' => 'sort',
            'labelKey' => 'filters.sort',
            'options' => $sortOptions,
            'selected' => $filter->sort,
        ]) ?>

        <div class="filters__actions">
            <button type="submit" class="button button--primary"><?= $this->t('filters.apply') ?></button>
            <?php if ($filter->isFiltered()): ?>
                <a class="button button--ghost" href="/forklifts"><?= $this->t('filters.clear') ?></a>
            <?php endif; ?>
        </div>
    </form>

    <p class="results-summary" role="status"><?= $this->t('forklifts.results', ['count' => $page->total]) ?></p>

    <?php if ($page->isEmpty()): ?>
        <p class="empty-state"><?= $this->t('forklifts.empty') ?></p>
    <?php else: ?>
        <ul class="card-grid">
            <?php foreach ($page->items as $forklift): ?>
                <li><?= $this->partial('partials/forklift-card', ['forklift' => $forklift]) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <?= $this->partial('partials/pagination', ['page' => $page, 'path' => '/forklifts', 'query' => $filter->toQuery()]) ?>
</section>
