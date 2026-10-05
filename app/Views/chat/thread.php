<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="lp-panel" style="max-width:52rem;margin:0 auto;">
    <div class="lp-panel__head">
            <a class="lp-more" href="<?= route_to('chat_index') ?>">&larr; Semua pesan</a>

            <div class="lp-row" style="gap:.5rem;flex-wrap:nowrap;">
                <span class="lp-avatar" aria-hidden="true">
                    <?php if (! empty($thread['counterparty_photo'])): ?>
                        <img src="<?= esc((string) url_gambar($thread['counterparty_photo'])) ?>"
                             alt="" width="40" height="40"
                             style="border-radius:999px;object-fit:cover;">
                    <?php else: ?>
                        <?= esc(inisial((string) $thread['counterparty_name'])) ?>
                    <?php endif; ?>
                </span>

                <div>
                    <strong><?= esc((string) $thread['counterparty_name']) ?></strong>
                    <?php if (! empty($thread['counterparty_slug'])): ?>
                        <br>
                        <a class="lp-small" href="<?= route_to('storefront', (string) $thread['counterparty_slug']) ?>">
                            Lihat toko &rarr;
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if (! empty($thread['subject'])): ?>
            <p class="lp-small lp-muted">Topik: <?= esc((string) $thread['subject']) ?></p>
        <?php endif; ?>

        <div class="lp-messages" data-messages>
            <?php if ($thread['messages'] === []): ?>
                <p class="lp-muted lp-small">
                    Belum ada pesan. Mulai percakapan di bawah ini.
                </p>
            <?php endif; ?>

            <?php foreach ($thread['messages'] as $message): ?>
                <div class="lp-message <?= (int) $message['sender_id'] === user_id() ? 'lp-message--mine' : '' ?>">
                    <div><?= nl2br(esc((string) $message['body'])) ?></div>
                    <time datetime="<?= esc(date('c', strtotime((string) $message['created_at']))) ?>">
                        <?= esc(waktu_smart((string) $message['created_at'])) ?>
                    </time>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($quick_replies !== []): ?>
            <div class="lp-quick-replies">
                <?php foreach ($quick_replies as $reply): ?>
                    <button class="lp-chip" type="button" data-quick-reply="<?= esc((string) $reply['body']) ?>">
                        <?= esc((string) $reply['body']) ?>
                    </button>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="post" action="<?= route_to('chat_send', (int) $thread['id']) ?>" style="display:flex;gap:.5rem;align-items:flex-end;margin-top:.75rem;">
            <?= csrf_field() ?>

            <div class="lp-field" style="flex:1;margin:0;">
                <label for="body" class="lp-visually-hidden">Pesan</label>
                <textarea id="body" name="body" rows="2" maxlength="2000" required
                          placeholder="Tulis pesan Anda&hellip;"
                          data-chat-input><?= esc(old('body')) ?></textarea>
            </div>

            <button class="lp-btn lp-btn--solid" type="submit">Kirim</button>
        </form>

        <p class="lp-small lp-muted">
            Jangan kirim data pribadi atau pembayaran di luar aplikasi.
        </p>
</div>

<?= $this->endSection() ?>
