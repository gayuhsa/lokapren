<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="lp-section__head">
    <div>
        <h1>Cerita Saya</h1>
        <p class="lp-muted">
            Artikel yang muncul di halaman Cerita. Cerita yang terbit membantu calon pembeli
            memahami proses di balik karya.
        </p>
    </div>

    <a class="lp-btn lp-btn--solid" href="<?= route_to('seller_blog_create') ?>">Tulis cerita</a>
</div>

<?php if ($posts === []): ?>
    <div class="lp-empty">
        <p><strong>Belum ada cerita.</strong></p>
        <p class="lp-small">
            Cerita adalah cara paling murah untuk membangun kepercayaan. Tulis proses, alat, dan
            alasan di balik harga karya Anda.
        </p>
        <a class="lp-btn lp-btn--solid" href="<?= route_to('seller_blog_create') ?>">Tulis cerita pertama</a>
    </div>
<?php else: ?>
    <div class="lp-table-wrap">
        <table class="lp-table">
            <thead>
                <tr>
                    <th scope="col">Judul</th>
                    <th scope="col">Status</th>
                    <th scope="col">Dibaca</th>
                    <th scope="col">Diperbarui</th>
                    <th scope="col"><span class="lp-visually-hidden">Aksi</span></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($posts as $post): ?>
                    <?php
                    $isPublished = (string) ($post['status'] ?? '') === 'published';
                    ?>
                    <tr>
                        <td>
                            <strong><?= esc((string) $post['title']) ?></strong>
                            <div class="lp-small lp-muted">
                                <?php if (! empty($post['excerpt'])): ?>
                                    <?= esc(mb_strimwidth((string) $post['excerpt'], 0, 90, '…')) ?>
                                <?php endif; ?>
                            </div>

                            <?php if ($isPublished): ?>
                                <a class="lp-small"
                                   href="<?= route_to('blog_show', (string) $post['id']) ?>">Lihat di situs &rsaquo;</a>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="lp-pill <?= $isPublished ? 'lp-pill--ok' : 'lp-pill--warn' ?>">
                                <?= $isPublished ? 'Terbit' : 'Draf' ?>
                            </span>
                        </td>
                        <td class="lp-num"><?= esc((string) ($post['view_count'] ?? 0)) ?></td>
                        <td class="lp-small lp-muted"><?= format_tanggal($post['updated_at']) ?></td>
                        <td>
                            <div class="lp-row">
                                <a class="lp-btn lp-btn--sm lp-btn--outline"
                                   href="<?= route_to('seller_blog_edit', (string) $post['id']) ?>">Ubah</a>

                                <form method="post"
                                      action="<?= route_to('seller_blog_delete', (string) $post['id']) ?>"
                                      data-confirm="Hapus cerita ini?">
                                    <?= csrf_field() ?>
                                    <button class="lp-btn lp-btn--sm lp-btn--ghost" type="submit">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?= $this->endSection() ?>
