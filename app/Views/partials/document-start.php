<?php

/**
 * Reusable document opening: <!doctype>, <head>, <body>.
 *
 * Always loads the shared layer first (tokens -> base -> site chrome), then any
 * page-specific stylesheet passed in $stylesheets, so custom properties are
 * guaranteed to be defined before anything uses them.
 *
 * @var string             $title
 * @var string             $bodyClass     Extra class for <body>, or ''.
 * @var array<int,string>  $stylesheets   Page-specific paths, e.g. ['assets/css/auth.css'].
 * @var string             $description
 */

$bodyClass    = $bodyClass    ?? '';
$stylesheets  = $stylesheets  ?? [];
$description  = $description  ?? 'Lokapren — kurasi kriya adiluhung perajin Magelang.';

$shared = [
    'assets/css/tokens.css',
    'assets/css/site.css',
    'assets/css/partials/masthead.css',
    'assets/css/partials/footer.css',
];
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($title) ?> &middot; Lokapren</title>
    <meta name="description" content="<?= esc($description) ?>">
    <?php foreach (array_merge($shared, $stylesheets) as $sheet) : ?>
        <link rel="stylesheet" href="<?= base_url($sheet) ?>">
    <?php endforeach ?>
</head>
<body<?= $bodyClass === '' ? '' : ' class="' . esc($bodyClass, 'attr') . '"' ?>>
