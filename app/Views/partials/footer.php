<?php

/**
 * Site footer.
 */
$sanggar = sanggar_footer();
?>
<footer class="lp-footer">
    <div class="lp-container lp-footer__inner">
        <div class="lp-footer__brand">
            <strong>Lokapren</strong>
            <p>
                Marketplace kriya Magelang. Setiap karya dicetak langsung oleh pengrajin
                di Nearby, tanpa lewat panjang.
            </p>
        </div>

        <div class="lp-footer__col">
            <h2>Jelajahi</h2>
            <a href="<?= route_to('catalog') ?>">Katalog Kriya</a>
            <a href="<?= route_to('locator') ?>">Peta Pengrajin</a>
            <a href="<?= route_to('blog_index') ?>">Cerita &amp; Panduan</a>
        </div>

        <div class="lp-footer__col">
            <h2>Akun</h2>
            <a href="<?= route_to('cart') ?>">Keranjang</a>
            <a href="<?= route_to('orders') ?>">Pesanan Saya</a>
            <a href="<?= route_to('chat_index') ?>">Pesan</a>
        </div>

        <?php if ($sanggar !== []): ?>
            <div class="lp-footer__col">
                <h2>Sanggar Pilihan</h2>
                <?php foreach (array_slice($sanggar, 0, 5) as $shop): ?>
                    <a href="<?= route_to('storefront', (string) $shop['slug']) ?>">
                        <?= esc((string) $shop['display_name']) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="lp-footer__base">
        <div class="lp-container">
            <small>&copy; <?= esc(date('Y')) ?> Lokapren. Dibuat untuk pengrajin Magelang.</small>
        </div>
    </div>
</footer>
