<?php

/**
 * Flash messages and validation errors.
 *
 * Errors are rendered as a list above the form they belong to, so a customer
 * sees what went wrong without losing what they typed — every failing form
 * redirects back `withInput()`.
 */
$errors = session('errors');
$error  = session('error');
?>
<?php if ($error !== null && $error !== ''): ?>
    <div class="lp-alert lp-alert--error" role="alert"><?= esc((string) $error) ?></div>
<?php endif; ?>

<?php if ($errors !== null && $errors !== []): ?>
    <div class="lp-alert lp-alert--error" role="alert">
        <strong>Periksa kembali isian Anda:</strong>
        <ul>
            <?php foreach ((array) $errors as $field => $message): ?>
                <li><?= esc(is_array($message) ? implode(' ', $message) : (string) $message) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php foreach (['success' => 'success', 'message' => 'success'] as $key => $level): ?>
    <?php $flash = session($key); ?>
    <?php if ($flash !== null && $flash !== ''): ?>
        <div class="lp-alert lp-alert--<?= esc($level) ?>" role="status"><?= esc((string) $flash) ?></div>
    <?php endif; ?>
<?php endforeach; ?>
