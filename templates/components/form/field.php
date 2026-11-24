<?php
/**
 * Pole formularza z etykietą i komunikatem błędu powiązanym przez aria-describedby.
 *
 * @var \App\View\View $this
 * @var string $name
 * @var string $labelKey
 * @var string|null $type
 * @var string|null $value
 * @var string|null $error
 * @var bool|null $required
 * @var string|null $autocomplete
 * @var string|null $hintKey
 * @var array<string, string|int>|null $hintParams
 */

$type ??= 'text';
$value ??= '';
$error ??= null;
$required ??= false;
$autocomplete ??= null;
$hintKey ??= null;
$hintParams ??= [];

$id = 'field-' . $name;
$describedBy = array_filter([$hintKey !== null ? $id . '-hint' : null, $error !== null ? $id . '-error' : null]);
?>
<div class="form-field<?= $error !== null ? ' form-field--invalid' : '' ?>">
    <label for="<?= $this->e($id) ?>">
        <?= $this->t($labelKey) ?>
        <?php if ($required): ?><span class="form-field__required" aria-hidden="true">*</span><?php endif; ?>
    </label>

    <?php if ($hintKey !== null): ?>
        <p class="form-field__hint" id="<?= $this->e($id) ?>-hint"><?= $this->t($hintKey, $hintParams) ?></p>
    <?php endif; ?>

    <input
        id="<?= $this->e($id) ?>"
        name="<?= $this->e($name) ?>"
        type="<?= $this->e($type) ?>"
        <?php if ($type !== 'password'): ?>value="<?= $this->e($value) ?>"<?php endif; ?>
        <?php if ($required): ?>required<?php endif; ?>
        <?php if ($autocomplete !== null): ?>autocomplete="<?= $this->e($autocomplete) ?>"<?php endif; ?>
        <?php if ($error !== null): ?>aria-invalid="true"<?php endif; ?>
        <?php if ($describedBy !== []): ?>aria-describedby="<?= $this->e(implode(' ', $describedBy)) ?>"<?php endif; ?>
    >

    <?php if ($error !== null): ?>
        <p class="form-field__error" id="<?= $this->e($id) ?>-error"><?= $this->e($error) ?></p>
    <?php endif; ?>
</div>
