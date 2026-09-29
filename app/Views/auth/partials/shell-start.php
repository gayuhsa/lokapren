<?php

/**
 * Auth shell opening: document, site masthead, then the two-column auth
 * layout — marketing column on the left, the form column on the right.
 *
 * Pairs with auth/partials/shell-end.php.
 *
 * @var string $title
 */

?>
<?= view('partials/document-start', [
    'title'       => $title,
    'stylesheets' => ['assets/css/auth.css'],
]) ?>

<?= view('partials/masthead') ?>

<main class="lok-shell auth-layout">
    <?= view('auth/partials/hero') ?>

    <div class="auth-column">
