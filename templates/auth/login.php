<?php
/**
 * @var \App\View\View $this
 * @var string $email
 * @var array<string, string> $errors
 * @var string|null $formError
 * @var string|null $notice
 */
?>
<section class="auth-card" aria-labelledby="login-title">
    <h1 id="login-title"><?= $this->t('auth.login.title') ?></h1>

    <?php if ($notice !== null): ?>
        <div class="alert alert--success" role="status"><?= $this->e($notice) ?></div>
    <?php endif; ?>

    <?php if ($formError !== null): ?>
        <div class="alert alert--error" role="alert"><?= $this->e($formError) ?></div>
    <?php endif; ?>

    <form method="post" action="/login" novalidate>
        <?= $this->csrfField() ?>

        <?= $this->partial('components/form/field', [
            'name' => 'email',
            'type' => 'email',
            'labelKey' => 'auth.field.email',
            'value' => $email,
            'error' => $errors['email'] ?? null,
            'required' => true,
            'autocomplete' => 'username',
        ]) ?>

        <?= $this->partial('components/form/field', [
            'name' => 'password',
            'type' => 'password',
            'labelKey' => 'auth.field.password',
            'error' => $errors['password'] ?? null,
            'required' => true,
            'autocomplete' => 'current-password',
        ]) ?>

        <button type="submit" class="button button--primary"><?= $this->t('auth.login.submit') ?></button>
    </form>

    <p><a href="/forgot-password"><?= $this->t('auth.login.forgot_link') ?></a></p>
</section>
