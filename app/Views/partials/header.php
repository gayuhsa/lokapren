<?php

/**
 * Shared auth shell: <head>, masthead, and the left (brand) column.
 *
 * @var string $title
 * @var string $activeTab  'login' or 'register'
 */
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($title) ?> &middot; Lokapren</title>
    <meta name="description" content="Lokapren — kurasi kriya adiluhung perajin Magelang.">
    <!-- Design tokens + base/auth-card. Must load first: the two component
         sheets below consume its custom properties. -->
    <link rel="stylesheet" href="<?= base_url('assets/css/auth.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/partials/header.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/partials/footer.css') ?>">
</head>
<body class="lokapren-auth">

<header class="masthead">
    <div class="lok-shell masthead__inner">
        <a class="brand" href="<?= base_url() ?>">
            <span class="brand__name">Lokapren</span>
            <span class="brand__tag">Kriya Magelang</span>
        </a>

        <nav aria-label="Navigasi utama">
            <ul class="masthead__nav">
                <li><a href="<?= base_url() ?>">Storefront</a></li>
                <li><a href="<?= base_url('marketplace') ?>">Katalog &amp; Lokasi</a></li>
                <?php // Sections the application has not routed yet. ?>
                <li><span>Artisan</span></li>
                <li><span>Detail</span></li>
                <li><span>Galeri</span></li>
                <li><span>Obrolan Antar UMKM</span></li>
                <li><span>Pesan &amp; Checkout</span></li>
                <li><span>Dashboard Mitra UMKM</span></li>
            </ul>
        </nav>
    </div>
</header>

<main class="lok-shell auth-layout">
    <section class="hero" aria-labelledby="hero-title">
        <span class="chip">Portal Resmi Adiluhung Kriya Magelang</span>

        <h1 class="hero__title" id="hero-title">Gerbang Kurasi &amp; Warisan Kriya Adiluhung Magelang</h1>

        <p class="hero__lead">
            Menghubungkan ketelatenan empu pahat Muntilan, gerabah pusaka Klipoh, anyaman Candirejo,
            dan kriya batik Borobudur ke dalam ekosistem bernilai lestari.
        </p>

        <ul class="trust">
            <li class="trust__item">
                <span class="trust__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 3 4 6.5v5c0 4.6 3.2 8.4 8 9.5 4.8-1.1 8-4.9 8-9.5v-5Z"/>
                        <path d="m9 12 2 2 4-4"/>
                    </svg>
                </span>
                <div>
                    <h2 class="trust__title">100% Terverifikasi Empu Lokal</h2>
                    <p class="trust__text">Karya bersertifikat asal muasal sanggar dan silsilah kriya resmi.</p>
                </div>
            </li>
            <li class="trust__item">
                <span class="trust__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 8.5 12 4l9 4.5v7L12 20l-9-4.5Z"/>
                        <path d="M12 12v8M3 8.5 12 13l9-4.5"/>
                    </svg>
                </span>
                <div>
                    <h2 class="trust__title">Garansi Asli &amp; Kurir Pesan Antar</h2>
                    <p class="trust__text">Proteksi peti kayu, penanganan karya bernilai tinggi, dan pelacakan kurir terpadu.</p>
                </div>
            </li>
            <li class="trust__item">
                <span class="trust__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="9" cy="8" r="3.2"/>
                        <path d="M3.5 19c0-3 2.5-5 5.5-5s5.5 2 5.5 5"/>
                        <circle cx="17" cy="9.5" r="2.4"/>
                        <path d="M15.5 14.2c2.7.2 5 1.9 5 4.8"/>
                    </svg>
                </span>
                <div>
                    <h2 class="trust__title">Langsung Memberdayakan Pengrajin</h2>
                    <p class="trust__text">Nilai pembelian disalurkan adil transparan tanpa potongan perantara gelap.</p>
                </div>
            </li>
        </ul>

        <div class="partner-band">
            <p class="partner-band__label">Bergabung bersama 480+ Empu</p>
            <ul class="partner-band__logos">
                <li>Sanggar Klipoh Karanganyar</li>
                <li>Gerabah Bakar Sekam Alami</li>
                <li>Sentra Ukiran Muntilan</li>
                <li class="tag-mint">#KriyaBerdaya</li>
            </ul>
        </div>
    </section>

    <div class="auth-column">
