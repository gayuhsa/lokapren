<?php

/**
 * Site header: brand, search, craft menu, cart, chat and account actions.
 *
 * Data comes from the view helpers rather than the controller, so a page cannot
 * forget to pass it and the header stays consistent across the site.
 */
$categories = kategori_nav();
$currentUser = auth()->user();
$isSeller    = $currentUser !== null && in_array('seller', $currentUser->getGroups(), true);
?>
<header class="lp-header">
    <div class="lp-header__bar">
        <div class="lp-container lp-header__inner">
            <a class="lp-brand" href="<?= site_url() ?>">
                <span class="lp-brand__mark" aria-hidden="true">LP</span>
                <span class="lp-brand__text">
                    <strong>Lokapren</strong>
                    <small>Kriya Magelang</small>
                </span>
            </a>

            <form class="lp-search" action="<?= site_url('catalog') ?>" method="get" role="search">
                <label class="lp-visually-hidden" for="lp-search">Cari kriya atau pengrajin</label>
                <input
                    id="lp-search"
                    type="search"
                    name="q"
                    placeholder="Cari gebyok, batik, gerabah…"
                    value="<?= esc((string) ($search ?? service('request')->getGet('q'))) ?>"
                >
                <button type="submit">Cari</button>
            </form>

            <nav class="lp-header__actions" aria-label="Akun">
                <?php if ($currentUser === null): ?>
                    <a class="lp-btn lp-btn--ghost" href="<?= route_to('login') ?>">Masuk</a>
                    <a class="lp-btn lp-btn--solid" href="<?= route_to('register') ?>">Daftar</a>
                <?php else: ?>
                    <a class="lp-icon-link" href="<?= route_to('chat_index') ?>" title="Pesan">
                        <span aria-hidden="true">&#9993;</span>
                        <span class="lp-visually-hidden">Pesan</span>
                    </a>

                    <?php
                        // Read through CartService, which scopes to the
                        // signed-in user; a guest simply sees no badge.
                        $cartCount = $currentUser === null
                            ? 0
                            : service('cartService')->badgeCount((int) $currentUser->id);
                        ?>
                    <a class="lp-icon-link lp-icon-link--count" href="<?= route_to('cart') ?>" title="Keranjang">
                        <span aria-hidden="true">&#128722;</span>
                        <span class="lp-visually-hidden">Keranjang</span>
                        <?php if ($cartCount > 0): ?>
                            <span class="lp-badge"><?= esc((string) $cartCount) ?></span>
                        <?php endif; ?>
                    </a>

                    <?php if ($isSeller): ?>
                        <a class="lp-btn lp-btn--ghost" href="<?= route_to('seller_dashboard') ?>">Dasbor Mitra</a>
                    <?php endif; ?>

                    <a class="lp-btn lp-btn--ghost" href="<?= route_to('profile') ?>">
                        <?= esc($currentUser->username) ?>
                    </a>
                    <a class="lp-btn lp-btn--outline" href="<?= site_url('logout') ?>">Keluar</a>
                <?php endif; ?>
            </nav>
        </div>
    </div>

    <?php if ($categories !== []): ?>
        <nav class="lp-header__nav" aria-label="Kategori kriya">
            <div class="lp-container lp-header__nav-inner">
                <a href="<?= route_to('catalog') ?>">Semua Kriya</a>
                <?php foreach ($categories as $category): ?>
                    <a href="<?= route_to('catalog') ?>?category=<?= esc((string) $category['slug']) ?>">
                        <?= esc((string) $category['name']) ?>
                    </a>
                <?php endforeach; ?>
                <a class="lp-header__nav-accent" href="<?= route_to('locator') ?>">Peta Pengrajin</a>
                <a href="<?= route_to('blog_index') ?>">Cerita</a>
            </div>
        </nav>
    <?php endif; ?>
</header>
