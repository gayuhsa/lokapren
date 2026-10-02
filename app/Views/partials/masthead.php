<?php

/**
 * Site-wide masthead. Shared by every page, auth and public alike.
 *
 * A nav entry with a null 'url' has no route yet and renders as inert text
 * rather than a dead link.
 *
 * @var string|null $current  Key of the nav entry for the current page, or null.
 */

$current = $current ?? null;

$navItems = [
    ['key' => 'storefront', 'label' => 'Storefront', 'url' => base_url()],
    ['key' => 'catalog', 'label' => 'Katalog & Lokasi', 'url' => base_url('marketplace')],
    ['key' => 'artisan', 'label' => 'Artisan', 'url' => null],
    ['key' => 'detail', 'label' => 'Detail', 'url' => null],
    ['key' => 'gallery', 'label' => 'Galeri', 'url' => null],
    ['key' => 'chat', 'label' => 'Obrolan Antar UMKM', 'url' => null],
    ['key' => 'checkout', 'label' => 'Pesan & Checkout', 'url' => null],
    ['key' => 'dashboard', 'label' => 'Dashboard Mitra UMKM', 'url' => null],
];
?>

<header class="masthead">
    <div class="lok-shell masthead__inner">
        <a class="brand" href="<?= base_url() ?>">
            <span class="brand__name">Lokapren</span>
            <span class="brand__tag">Kriya Magelang</span>
        </a>

        <nav aria-label="Navigasi utama">
            <ul class="masthead__nav">
                <?php foreach ($navItems as $item) : ?>
                    <li>
                        <?php if ($item['url'] === null) : ?>
                            <span><?= esc($item['label']) ?></span>
                        <?php else : ?>
                            <a href="<?= esc($item['url'], 'attr') ?>"
                               <?= $current === $item['key'] ? ' aria-current="page"' : '' ?>><?= esc($item['label']) ?></a>
                        <?php endif ?>
                    </li>
                <?php endforeach ?>
            </ul>
        </nav>

        <div class="masthead__account">
            <?php if (auth()->loggedIn()) : ?>
                <span class="masthead__who"><?= esc(auth()->user()->username) ?></span>

                <?php // Logging out is a state change, so it posts a CSRF
                // token rather than following a link. ?>
                <form action="<?= site_url('logout') ?>" method="post">
                    <?= csrf_field() ?>
                    <button type="submit">Keluar</button>
                </form>
            <?php else : ?>
                <a href="<?= site_url('login') ?>">Masuk</a>
                <a href="<?= site_url('register') ?>">Daftar</a>
            <?php endif ?>
        </div>
    </div>
</header>
