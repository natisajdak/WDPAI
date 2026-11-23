<?php
/**
 * Układ e-maili. Klienty pocztowe nie wczytują zewnętrznych arkuszy CSS,
 * dlatego to jedyne miejsce w projekcie ze stylami osadzonymi w dokumencie.
 *
 * @var \App\View\View $this
 * @var string $content
 * @var string $subject
 */
?>
<!doctype html>
<html lang="<?= $this->e($this->locale()) ?>">
<head>
    <meta charset="utf-8">
    <title><?= $this->e($subject) ?></title>
    <style>
        body { margin: 0; padding: 24px; background: #f3f4f6; font-family: Arial, Helvetica, sans-serif; color: #1f2933; }
        .mail { max-width: 560px; margin: 0 auto; padding: 32px; background: #ffffff; border-top: 6px solid #f2b705; }
        .mail h1 { margin-top: 0; font-size: 20px; }
        .mail .button { display: inline-block; padding: 12px 20px; background: #1f2933; color: #ffffff; text-decoration: none; font-weight: bold; }
        .mail .muted { color: #52606d; font-size: 13px; }
    </style>
</head>
<body>
    <div class="mail">
        <?= $content ?>
        <p class="muted"><?= $this->t('mail.footer') ?></p>
    </div>
</body>
</html>
