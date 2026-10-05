<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($title ?? 'Lokapren') ?> — Lokapren</title>
    <meta name="description" content="<?= esc($meta_description ?? 'Marketplace kerajinan tangan Magelang dari pengrajin lokal. Temukan sanggar, pesan langsung, dan beli karya asli.') ?>">
    <link rel="icon" href="<?= base_url('favicon.ico') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/lokapren.css') ?>">
    <?= $this->renderSection('head') ?>
    <?= csrf_meta() ?>
</head>
<body class="lp-body">
<a class="lp-skip" href="#lp-main">Lompat ke konten utama</a>

<?= view('partials/header') ?>

<main id="lp-main" class="lp-main">
    <?= view('partials/flash') ?>
    <?= $this->renderSection('content') ?>
</main>

<?= view('partials/footer') ?>

<script src="<?= base_url('assets/js/lokapren.js') ?>" defer></script>
</body>
</html>
