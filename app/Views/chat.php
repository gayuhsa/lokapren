<?php

/**
 * The message centre: conversation list, active thread, and a trust rail that
 * explains where the money and the craft actually come from.
 *
 * @var string                   $tab
 * @var list<array<string, mixed>> $threads
 * @var int                      $allCount
 * @var int                      $unread
 * @var array<string, mixed>|null $thread
 * @var string|null $message
 * @var string|null $error
 */

use App\Services\Rupiah;

$title       = 'Pesan & Obrolan Kriya';
$current     = 'chat';
$bodyClass   = 'page-chat';
$stylesheets = ['assets/css/chat.css'];
$description = 'Ngobrol langsung dengan pengrajin Lokapren sebelum dan sesudah pesanan.';

$chatSvc = service("App\Services\Chat"); $u = auth()->user(); $isSeller = $chatSvc && $u && $chatSvc->roleFor($u) === "seller";
$unreadCol = $isSeller ? 'store_unread_count' : 'customer_unread_count';

echo view('partials/document-start', [
    'title'       => $title,
    'bodyClass'   => $bodyClass,
    'stylesheets' => $stylesheets,
    'description' => $description,
]);
?>

<?= view('partials/masthead', ['current' => $current]) ?>

<main class="chat-shell">

    <?php // Column 1: the caller's own threads only. ?>
    <section class="chat-list" aria-label="Daftar percakapan">
        <header class="chat-list__head">
            <h1>Pesan &amp; Obrolan Kriya</h1>

            <form class="chat-search" action="<?= esc(site_url('chat'), 'attr') ?>" method="get" role="search">
                <label class="lok-visually-hidden" for="chat-q">Cari percakapan</label>
                <input id="chat-q" type="search" name="q" placeholder="Cari percakapan atau nama sanggar">
            </form>

            <nav class="chat-tabs" aria-label="Filter percakapan">
                <a class="chat-tab<?= $tab === 'all' ? ' is-active' : '' ?>"
                   href="<?= esc(site_url('chat'), 'attr') ?>"
                   <?= $tab === 'all' ? 'aria-current="page"' : '' ?>>
                    Semua <span class="chat-tab__count"><?= $allCount ?></span>
                </a>
                <a class="chat-tab<?= $tab === 'unread' ? ' is-active' : '' ?>"
                   href="<?= esc(site_url('chat/tab/unread'), 'attr') ?>"
                   <?= $tab === 'unread' ? 'aria-current="page"' : '' ?>>
                    Belum dibaca
                    <?php if ($unread > 0) : ?>
                        <span class="chat-tab__count chat-tab__count--alert"><?= $unread ?></span>
                    <?php endif ?>
                </a>
                <a class="chat-tab<?= $tab === 'orders' ? ' is-active' : '' ?>"
                   href="<?= esc(site_url('chat/tab/orders'), 'attr') ?>"
                   <?= $tab === 'orders' ? 'aria-current="page"' : '' ?>>
                    Pesanan aktif
                </a>
            </nav>
        </header>

        <?php if ($message !== null) : ?>
            <p class="notice notice--ok chat-flash" role="status"><?= esc($message) ?></p>
        <?php endif ?>
        <?php if ($error !== null) : ?>
            <p class="notice notice--error chat-flash" role="alert"><?= esc($error) ?></p>
        <?php endif ?>

        <?php if ($threads === []) : ?>
            <p class="chat-list__empty">
                Belum ada percakapan. Mulai dari halaman sanggar atau karya untuk menulis pesan.
            </p>
        <?php else : ?>
            <ul class="chat-threads">
                <?php foreach ($threads as $row) : ?>
                    <?php
                    $threadUnread = (int) $row[$unreadCol];
                    $isOpen       = $thread !== null && (int) $thread['conversation']['id'] === (int) $row['id'];
                    ?>
                    <li>
                        <a class="chat-thread<?= $isOpen ? ' is-open' : '' ?><?= $threadUnread > 0 ? ' is-unread' : '' ?>"
                           href="<?= esc(site_url('chat/' . (int) $row['id']), 'attr') ?>"
                           <?= $isOpen ? 'aria-current="page"' : '' ?>>
                            <span class="chat-thread__avatar" aria-hidden="true">
                                <?= esc(mb_substr((string) $row['store_name'], 0, 1)) ?>
                            </span>

                            <span class="chat-thread__body">
                                <span class="chat-thread__top">
                                    <span class="chat-thread__name"><?= esc((string) $row['store_name']) ?></span>
                                    <?php if ($row['last_message_at'] !== null) : ?>
                                        <time class="chat-thread__time"
                                              datetime="<?= esc(date('c', strtotime((string) $row['last_message_at'])), 'attr') ?>">
                                            <?= esc(date('j M H:i', strtotime((string) $row['last_message_at']))) ?>
                                        </time>
                                    <?php endif ?>
                                </span>

                                <span class="chat-thread__preview">
                                    <?= $row['last_message_preview'] === null
                                        ? 'Belum ada pesan'
                                        : esc((string) $row['last_message_preview']) ?>
                                </span>

                                <span class="chat-thread__meta">
                                    <?= $isSeller
                                        ? 'Pelanggan ' . esc((string) $row['customer_username'])
                                        : 'Terverifikasi' ?>
                                    <?php if ($threadUnread > 0) : ?>
                                        <span class="chat-thread__badge"><?= $threadUnread ?></span>
                                    <?php endif ?>
                                </span>
                            </span>
                        </a>
                    </li>
                <?php endforeach ?>
            </ul>
        <?php endif ?>
    </section>

    <?php // Column 2: the active thread, or an invitation to start one. ?>
    <section class="chat-thread-view" aria-label="Percakapan">

        <?php if ($thread === null) : ?>
            <div class="chat-placeholder">
                <h2>Pilih percakapan</h2>
                <p>
                    Pilih salah satu percakapan di sebelah kiri, atau buka halaman karya
                    lalu pilih <strong>Tanya Pengrajin</strong> untuk memulai obrolan baru.
                </p>
                <a class="btn btn--primary" href="<?= esc(base_url('marketplace'), 'attr') ?>">
                    Jelajahi Katalog
                </a>
            </div>
        <?php else : ?>
            <?php
            $conversation = $thread['conversation'];
            $messages     = $thread['messages'];
            $lastAt       = null;

            foreach ($messages as $messageRow) {
                $lastAt = $messageRow['created_at'];
            }
            ?>

            <header class="chat-head">
                <div class="chat-head__who">
                    <h2><?= esc((string) $conversation['store_name']) ?></h2>
                    <p>
                        <?php if ($conversation['verification_status'] === 'verified') : ?>
                            <span class="chip chip--verified">Terverifikasi</span>
                        <?php else : ?>
                            <span class="chip">Menunggu verifikasi</span>
                        <?php endif ?>
                        <?php if ($conversation['store_slug'] !== null) : ?>
                            <a href="<?= esc(base_url('store/' . $conversation['store_slug']), 'attr') ?>">Lihat profil sanggar</a>
                        <?php endif ?>
                    </p>
                </div>
            </header>

            <?php if ($thread['orders'] !== []) : ?>
                <section class="chat-context" aria-label="Pesanan terkait">
                    <h3>Pesanan kamu dengan sanggar ini</h3>
                    <ul>
                        <?php foreach ($thread['orders'] as $order) : ?>
                            <li>
                                <a href="<?= esc(site_url('order/' . (int) $order['id']), 'attr') ?>">
                                    <span class="chat-context__code"><?= esc((string) $order['order_code']) ?></span>
                                    <span class="chat-context__status">
                                        <?= esc(ucfirst(str_replace('_', ' ', (string) $order['status']))) ?>
                                    </span>
                                    <span class="chat-context__total"><?= esc(Rupiah::format((int) $order['total'])) ?></span>
                                </a>
                            </li>
                        <?php endforeach ?>
                    </ul>
                </section>
            <?php endif ?>

            <ol class="chat-messages">
                <?php if ($messages === []) : ?>
                    <li class="chat-messages__empty">
                        Belum ada pesan. Sapa pengrajin untuk menanyakan apa saja tentang karyanya.
                    </li>
                <?php endif ?>

                <?php foreach ($messages as $row) : ?>
                    <?php $mine = (int) $row['sender_id'] === (int) $conversation['customer_id'] ? 'out' : 'in'; ?>
                    <li class="chat-msg chat-msg--<?= $mine ?> chat-msg--<?= esc((string) $row['message_type'], 'attr') ?>">
                        <div class="chat-bubble">
                            <?php if (isset($row['product']) && $row['product'] !== null) : ?>
                                <a class="chat-card" href="<?= esc(base_url('product/' . $row['product']['slug']), 'attr') ?>">
                                    <span class="chat-card__label">Karya</span>
                                    <span class="chat-card__title"><?= esc((string) $row['product']['name']) ?></span>
                                </a>
                            <?php endif ?>

                            <?php if ($row['order_id'] !== null) : ?>
                                <span class="chat-card chat-card--order">
                                    <span class="chat-card__label">Pesanan</span>
                                    <span class="chat-card__title">
                                        <?= esc(site_url('order/' . (int) $row['order_id'])) ?>
                                    </span>
                                </span>
                            <?php endif ?>

                            <?php if ($row['body'] !== null && $row['body'] !== '') : ?>
                                <p class="chat-bubble__body"><?= nl2br(esc((string) $row['body'])) ?></p>
                            <?php endif ?>
                        </div>

                        <p class="chat-msg__foot">
                            <time datetime="<?= esc(date('c', strtotime((string) $row['created_at'])), 'attr') ?>">
                                <?= esc(date('j M, H:i', strtotime((string) $row['created_at']))) ?>
                            </time>
                            <?php if ($mine === 'out') : ?>
                                <span class="chat-msg__receipt"><?= $row['is_read'] ? 'Dibaca' : 'Terkirim' ?></span>
                            <?php endif ?>
                        </p>
                    </li>
                <?php endforeach ?>
            </ol>

            <div class="chat-composer">
                <?php if ($thread['quickReplies'] !== []) : ?>
                    <div class="chat-quick">
                        <?php foreach ($thread['quickReplies'] as $reply) : ?>
                            <button type="button" class="chat-quick__chip" data-quick-reply="<?= esc($reply, 'attr') ?>">
                                <?= esc($reply) ?>
                            </button>
                        <?php endforeach ?>
                    </div>
                <?php endif ?>

                <form action="<?= esc(site_url('chat/' . (int) $conversation['id'] . '/send'), 'attr') ?>" method="post">
                    <?= csrf_field() ?>
                    <label class="lok-visually-hidden" for="chat-body">Tulis pesan</label>
                    <textarea id="chat-body" name="body" rows="2" maxlength="2000"
                              placeholder="Tulis pesan untuk <?= esc((string) $conversation['store_name'], 'attr') ?>"
                              data-quick-reply-target><?= esc((string) ($_POST['body'] ?? '')) ?></textarea>
                    <button class="btn btn--primary" type="submit">Kirim</button>
                </form>

                <?php // Attaching a craft or order card is a real, checked
                // operation, so it is offered with a value the server verifies. ?>
                <div class="chat-attach">
                    <?php if ($thread['orders'] !== []) : ?>
                        <details class="chat-attach__group">
                            <summary>Lampirkan kartu pesanan</summary>
                            <?php foreach ($thread['orders'] as $order) : ?>
                                <form action="<?= esc(site_url('chat/' . (int) $conversation['id'] . '/send'), 'attr') ?>" method="post">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="order_id" value="<?= (int) $order['id'] ?>">
                                    <input type="hidden" name="body" value="Saya ingin menanyakan pesanan ini.">
                                    <button type="submit"><?= esc((string) $order['order_code']) ?></button>
                                </form>
                            <?php endforeach ?>
                        </details>
                    <?php endif ?>
                </div>
            </div>
        <?php endif ?>
    </section>

    <?php // Column 3: trust rail, static but true. ?>
    <aside class="chat-rail" aria-label="Tentang transaksi aman">
        <h2>Kenapa aman lewat Lokapren</h2>

        <ul class="chat-rail__list">
            <li>
                <h3>Harga dari pengrajin</h3>
                <p>Setiap karya ditawarkan langsung oleh pengrajin, tanpa markup reseller.</p>
            </li>
            <li>
                <h3>Escrow</h3>
                <p>Dana ditahan sampai kamu mengonfirmasi karya diterima dengan baik.</p>
            </li>
            <li>
                <h3>Garansi pahat</h3>
                <p>Retak atau patah saat pengiriman dilaporkan dan ditangani pengrajin.</p>
            </li>
        </ul>

        <div class="chat-rail__note">
            <h3>Motif Lokapren</h3>
            <p>
                Setiap pembelian menjaga pengrajin Magelang tetap bekerja di bingkunya,
                bukan tergantikan mesin.
            </p>
        </div>
    </aside>
</main>

<script>
    // Quick replies fill the composer instead of posting on their own, so the
    // customer can edit before sending.
    document.querySelectorAll('[data-quick-reply]').forEach(function (chip) {
        chip.addEventListener('click', function () {
            var target = document.querySelector('[data-quick-reply-target]');

            if (target) {
                target.value = chip.getAttribute('data-quick-reply') || '';
                target.focus();
            }
        });
    });
</script>

<?= view('partials/footer') ?>
<?= view('partials/document-end') ?>