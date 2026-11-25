<?php
/**
 * Znaczniki stanu wózka: status, ważność UDT, użycie teraz, otwarte usterki.
 * Kolor nigdy nie jest jedyną informacją: każdy znacznik ma tekst.
 *
 * @var \App\View\View $this
 * @var \App\Domain\ReadModel\ForkliftOverview $forklift
 */

use App\Domain\Enum\ForkliftStatus;

$statusModifier = match ($forklift->status) {
    ForkliftStatus::Available => 'success',
    ForkliftStatus::OutOfService => 'danger',
    ForkliftStatus::Retired => 'neutral',
};
?>
<ul class="badge-list">
    <li class="badge badge--<?= $statusModifier ?>"><?= $this->t($forklift->status->labelKey()) ?></li>

    <?php if ($forklift->status !== ForkliftStatus::Retired && !$forklift->isUdtValid): ?>
        <li class="badge badge--danger"><?= $this->t('forklift.udt_expired') ?></li>
    <?php endif; ?>

    <?php if ($forklift->isInUseNow): ?>
        <li class="badge badge--info"><?= $this->t('forklift.in_use_now') ?></li>
    <?php endif; ?>

    <?php if ($forklift->openFaultsCount > 0): ?>
        <li class="badge badge--warning"><?= $this->t('forklift.open_faults', ['count' => $forklift->openFaultsCount]) ?></li>
    <?php endif; ?>
</ul>
