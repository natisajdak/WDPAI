<?php
/**
 * @var \App\View\View $this
 * @var string $email
 * @var array<string, string> $errors
 */
?>
<section class="auth-card" aria-labelledby="forgot-title">
    <h1 id="forgot-title"><?= $this->t('auth.forgot.title') ?></h1>
    <p><?= $this->t('auth.forgot.intro') ?></p>

    <form method="post" action="/forgot-password" novalidate>
        <?= $this->csrfField() ?>

        <?= $this->partial('components/form/field', [
            'name' => 'email',
            'type' => 'email',
            'labelKey' => 'auth.field.email',
            'value' => $email,
            'error' => $errors['email'] ?? null,
            'required' => true,
            'autocomplete' => 'email',
        ]) ?>

        <button type="submit" class="button button--primary"><?= $this->t('auth.forgot.submit') ?></button>
    </form>

    <p><a href="/login"><?= $this->t('auth.back_to_login') ?></a></p>
</section>
