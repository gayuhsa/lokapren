<?php

/**
 * The customer's own order history.
 *
 * @var list<array<string, mixed>> $orders
 * @var string|null $message
 */

use App\Services\Rupiah;

$title       = 'Pesanan Saya';
$current     = 'checkout';
$bodyClass   = 'page-orders';
$stylesheets = ['assets/css/checkout.css'];
$description = 'Riwayat pesanan karya kriya di Lokapren.';

echo view('partials/document-start', [
    'title'       => $title,
    'bodyClass'   => $bodyClass,
    'stylesheets' => $stylesheets,
    'description' => $description,
]);
?>

<?= view('partials/masthead', ['current' => $current]) ?>

<main class="lok-shell page-main">
    <header class="page-head">
        <h1>Pesanan Saya</h1>
        <p class="page-head__lede">Setiap pesanan, status pengirimannya, dan aksi pembayarannya.</p>
    </header>

    <?php if ($message !== null) : ?>
        <p class="notice notice--ok" role="status"><?= esc($message) ?></p>
    <?php endif ?>

    <?php if ($orders === []) : ?>
        <section class="empty-state">
            <h2>Belum ada pesanan</h2>
            <p>Saat kamu memesan karya, pesanan itu akan muncul di sini dengan pelacakan pengiriman.</p>
            <a class="btn btn--primary" href="<?= esc(base_url('marketplace'), 'attr') ?>">Jelajahi Katalog</a>
        </section>
    <?php else : ?>
        <ul class="order-list">
            <?php foreach ($orders as $order) : ?>
                <li class="order-card">
                    <div class="order-card__head">
                        <p class="order-card__code"><?= esc((string) $order['order_code']) ?></p>
                        <p class="badge badge--<?= esc((string) $order['status'], 'attr') ?>">
                            <?= esc(ucfirst(str_replace('_', ' ', (string) $order['status']))) ?>
                        </p>
                    </div>

                    <p class="order-card__date">
                        Dibuat <?= esc(date('j F Y, H:i', strtotime((string) $order['created_at']))) ?>
                    </p>

                    <ul class="order-card__items">
                        <?php foreach (array_slice($order['items'], 0, 3) as $item) : ?>
                            <li>
                                <?= esc($item['product_name']) ?>
                                <span class="order-card__qty">&times; <?= (int) $item['qty'] ?></span>
                            </li>
                        <?php endforeach ?>
                        <?php if (count($order['items']) > 3) : ?>
                            <li class="order-card__more">+ <?= count($order['items']) - 3 ?> karya lain</li>
                        <?php endif ?>
                    </ul>

                    <p class="order-card__total">
                        Total <strong><?= esc(Rupiah::format((int) $order['total'])) ?></strong>
                    </p>

                    <a class="btn btn--ghost btn--sm" href="<?= esc(site_url('order/' . (int) $order['id']), 'attr') ?>">
                        Lihat detail
                    </a>
                </li>
            <?php endforeach ?>
        </ul>
    <?php endif ?>
</main>

<?= view('partials/footer') ?>
<?= view('partials/document-end') ?>