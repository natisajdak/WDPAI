<?php
/**
 * Lista wyboru z etykietą. Opcje przychodzą już przetłumaczone: wartość => etykieta.
 *
 * @var \App\View\View $this
 * @var string $name
 * @var string $labelKey
 * @var array<string|int, string> $options
 * @var string|int|null $selected
 * @var string|null $emptyKey klucz etykiety pustej opcji („wszystkie”); null = brak pustej opcji
 * @var string|null $error
 */

$selected ??= null;
$emptyKey ??= null;
$error ??= null;

$id = 'field-' . $name;
?>
<div class="form-field<?= $error !== null ? ' form-field--invalid' : '' ?>">
    <label for="<?= $this->e($id) ?>"><?= $this->t($labelKey) ?></label>
    <select
        id="<?= $this->e($id) ?>"
        name="<?= $this->e($name) ?>"
        <?php if ($error !== null): ?>aria-invalid="true" aria-describedby="<?= $this->e($id) ?>-error"<?php endif; ?>
    >
        <?php if ($emptyKey !== null): ?>
            <option value=""><?= $this->t($emptyKey) ?></option>
        <?php endif; ?>
        <?php foreach ($options as $value => $label): ?>
            <option value="<?= $this->e($value) ?>" <?= (string) $value === (string) $selected ? 'selected' : '' ?>><?= $this->e($label) ?></option>
        <?php endforeach; ?>
    </select>
    <?php if ($error !== null): ?>
        <p class="form-field__error" id="<?= $this->e($id) ?>-error"><?= $this->e($error) ?></p>
    <?php endif; ?>
</div>
