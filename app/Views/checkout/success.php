<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="lp-panel" style="text-align:center;max-width:44rem;margin:0 auto;">
    <span class="lp-brand__mark" aria-hidden="true">&#10003;</span>
    <h1>Pesanan berhasil dibuat</h1>

    <?php if (count($orders) > 1): ?>
        <p>
            Terima kasih. Keranjang Anda berisi <?= esc((string) count($orders)) ?> sanggar, jadi
            <?= esc((string) count($orders)) ?> pesanan dibuat sekaligus:
        </p>
    <?php else: ?>
        <p>Terima kasih. Pesanan Anda sudah masuk ke sanggar.</p>
    <?php endif; ?>

    <div class="lp-totals" style="max-width:22rem;margin:1.5rem auto;">
        <div class="lp-totals__row lp-totals__row--grand">
            <span>Total dibayar</span>
            <span><?= esc(rupiah($grand_total)) ?></span>
        </div>
    </div>

    <div class="lp-alert" style="text-align:left;">
        <strong>Cara pembayaran</strong>
        <p class="lp-small" style="margin:.25rem 0 0;">
            Belum ada payment gateway yang terhubung. Buka tiap pesanan di bawah untuk
            melihat nomor rekening sanggar, lalu konfirmasi transfer melalui WhatsApp
            atau chat di aplikasi.
        </p>
    </div>

    <div class="lp-stack" style="margin-top:1.5rem;text-align:left;">
        <?php foreach ($orders as $order): ?>
            <div class="lp-card">
                <div class="lp-card__body">
                    <div class="lp-row" style="justify-content:space-between;gap:.5rem;">
                        <div>
                            <strong><?= esc((string) $order['order_number']) ?></strong>
                            <?php if (! empty($order['shop_name'])): ?>
                                <br><span class="lp-muted"><?= esc((string) $order['shop_name']) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="lp-num">
                            <strong><?= esc(rupiah($order['grand_total'])) ?></strong>
                            <br>
                            <span class="lp-pill lp-pill--warn">
                                <?= esc((string) $order['progress']['label']) ?>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="lp-card__foot">
                    <a class="lp-btn lp-btn--sm lp-btn--outline"
                       href="<?= route_to('order_show', (int) $order['id']) ?>">Detail pesanan</a>
                    <form method="post" action="<?= route_to('order_chat', (int) $order['id']) ?>">
                        <?= csrf_field() ?>
                        <button class="lp-btn lp-btn--sm lp-btn--ghost" type="submit">Chat pengrajin</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="lp-row" style="justify-content:center;margin-top:1.5rem;">
        <a class="lp-btn lp-btn--solid" href="<?= route_to('orders') ?>">Lihat semua pesanan</a>
        <a class="lp-btn lp-btn--ghost" href="<?= route_to('catalog') ?>">Lanjut belanja</a>
    </div>
</div>

<?= $this->endSection() ?>
