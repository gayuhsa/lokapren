<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<p class="lp-breadcrumb">
    <a href="<?= site_url('account') ?>">Akun</a> &rsaquo; Pesan
</p>

<h1>Pesan</h1>

<p class="lp-muted">
    Tanyakan langsung ke pengrajin: bahan, ukuran, warna, atau waktu pengerjaan.
</p>

<?php if ($threads === []): ?>
    <div class="lp-empty">
        <p><strong>Belum ada percakapan.</strong></p>
        <p class="lp-small">
            Buka halaman karya lalu tekan "Tanya Pengrajin" untuk memulai obrolan.
        </p>
        <a class="lp-btn lp-btn--solid" href="<?= route_to('catalog') ?>">Jelajahi Katalog</a>
    </div>
<?php else: ?>
    <div class="lp-thread-list">
        <?php foreach ($threads as $thread): ?>
            <a class="lp-card <?= (int) $thread['unread'] > 0 ? 'is-unread' : '' ?>"
               href="<?= route_to('chat_thread', (int) $thread['id']) ?>">
                <div class="lp-row" style="flex-wrap:nowrap;gap:.75rem;">
                    <span class="lp-avatar" aria-hidden="true">
                        <?php if (! empty($thread['counterparty_photo'])): ?>
                            <img src="<?= esc((string) url_gambar($thread['counterparty_photo'])) ?>"
                                 alt="" width="40" height="40"
                                 style="border-radius:999px;object-fit:cover;">
                        <?php else: ?>
                            <?= esc(inisial((string) $thread['counterparty_name'])) ?>
                        <?php endif; ?>
                    </span>

                    <div style="flex:1;min-width:0;">
                        <strong><?= esc((string) $thread['counterparty_name']) ?></strong>
                        <?php if (! empty($thread['counterparty_shop'])): ?>
                            <br><small class="lp-muted">Sanggar</small>
                        <?php endif; ?>
                        <br><small class="lp-muted">
                            <?= esc((string) ($thread['last_message_at'] !== null
                                ? waktu_smart((string) $thread['last_message_at'])
                                : 'Belum ada pesan')) ?>
                        </small>
                    </div>

                    <?php if ((int) $thread['unread'] > 0): ?>
                        <span class="lp-badge"><?= esc((string) $thread['unread']) ?></span>
                    <?php endif; ?>
                </div>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if ($page > 1): ?>
        <nav class="lp-pagination" aria-label="Halaman percakapan">
            <a href="?page=<?= esc((string) ($page - 1)) ?>">Sebelumnya</a>
        </nav>
    <?php endif; ?>
<?php endif; ?>

<?= $this->endSection() ?>
