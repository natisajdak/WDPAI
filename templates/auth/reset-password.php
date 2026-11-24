<?php
/**
 * @var \App\View\View $this
 * @var string $token
 * @var bool $tokenValid
 * @var array<string, string> $errors
 * @var int $minLength
 */
?>
<section class="auth-card" aria-labelledby="reset-title">
    <h1 id="reset-title"><?= $this->t('auth.reset.title') ?></h1>

    <?php if (!$tokenValid): ?>
        <div class="alert alert--error" role="alert"><?= $this->t('auth.reset.invalid_token') ?></div>
        <p><a href="/forgot-password"><?= $this->t('auth.reset.request_new') ?></a></p>
    <?php else: ?>
        <form method="post" action="/reset-password" novalidate>
            <?= $this->csrfField() ?>
            <input type="hidden" name="token" value="<?= $this->e($token) ?>">

            <?= $this->partial('components/form/field', [
                'name' => 'password',
                'type' => 'password',
                'labelKey' => 'auth.field.new_password',
                'error' => $errors['password'] ?? null,
                'required' => true,
                'autocomplete' => 'new-password',
                'hintKey' => 'auth.reset.password_hint',
                'hintParams' => ['min' => $minLength],
            ]) ?>

            <?= $this->partial('components/form/field', [
                'name' => 'password_confirmation',
                'type' => 'password',
                'labelKey' => 'auth.field.password_confirmation',
                'error' => $errors['password_confirmation'] ?? null,
                'required' => true,
                'autocomplete' => 'new-password',
            ]) ?>

            <button type="submit" class="button button--primary"><?= $this->t('auth.reset.submit') ?></button>
        </form>
    <?php endif; ?>
</section>
