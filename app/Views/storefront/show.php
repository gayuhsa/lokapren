<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php
$profile  = $store['profile'];
$days     = ['1' => 'Senin', '2' => 'Selasa', '3' => 'Rabu', '4' => 'Kamis', '5' => 'Jumat', '6' => 'Sabtu', '7' => 'Minggu'];
$today    = (int) date('N');
?>

<section class="lp-hero" style="border-radius:var(--lp-radius-lg);overflow:hidden;margin-bottom:2rem;">
    <?php if (! empty($profile['cover_path'])): ?>
        <img src="<?= esc((string) url_gambar($profile['cover_path'])) ?>"
             alt="" style="width:100%;height:220px;object-fit:cover;">
    <?php endif; ?>

    <div class="lp-container" style="display:flex;gap:1.5rem;flex-wrap:wrap;align-items:flex-start;">
        <span class="lp-avatar" aria-hidden="true" style="width:88px;height:88px;font-size:1.8rem;">
            <?php if (! empty($profile['logo_path'])): ?>
                <img src="<?= esc((string) url_gambar($profile['logo_path'])) ?>"
                     alt="" width="88" height="88"
                     style="border-radius:999px;object-fit:cover;">
            <?php else: ?>
                <?= esc(inisial((string) $profile['display_name'])) ?>
            <?php endif; ?>
        </span>

        <div style="flex:1;min-width:16rem;">
            <h1><?= esc((string) $profile['display_name']) ?></h1>

            <?php if (! empty($profile['craft_focus'])): ?>
                <p class="lp-small lp-muted"><?= esc((string) $profile['craft_focus']) ?></p>
            <?php endif; ?>

            <?php if (! empty($profile['tagline'])): ?>
                <p><?= esc((string) $profile['tagline']) ?></p>
            <?php endif; ?>

            <p class="lp-small lp-muted">
                <?php if (! empty($profile['village']) || ! empty($profile['regency'])): ?>
                    <?= esc(lokasi_teks($profile)) ?>
                    &middot;
                <?php endif; ?>
                Anggota sejak <?= esc(format_tanggal((string) ($profile['member_since'] ?? null))) ?>
                <?php if ($is_open): ?>
                    &middot; <span class="lp-pill lp-pill--ok">Buka sekarang</span>
                <?php else: ?>
                    &middot; <span class="lp-pill lp-pill--bad">Tutup sekarang</span>
                <?php endif; ?>
            </p>

            <?php if ((int) $profile['rating_count'] > 0): ?>
                <?= bintang($profile['rating_average'], $profile['rating_count']) ?>
            <?php endif; ?>

            <?php if (! empty($profile['latitude']) && ! empty($profile['longitude'])): ?>
                <p class="lp-small" style="margin-top:.5rem;">
                    <a href="<?= route_to('locator') ?>" target="_blank" rel="noopener noreferrer">
                        Lihat di Peta &rarr;
                    </a>
                </p>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php if (! empty($profile['description'])): ?>
    <div class="lp-panel">
        <h2>Tentang sanggar ini</h2>
        <p><?= nl2br(esc((string) $profile['description'])) ?></p>

        <?php if (! empty($profile['owner_name'])): ?>
            <p class="lp-small lp-muted">Dikelola oleh <?= esc((string) $profile['owner_name']) ?>.</p>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php if ($store['products'] !== []): ?>
    <section class="lp-section">
        <div class="lp-section__head">
            <h2>Karya dari sanggar ini</h2>
            <span class="lp-muted lp-small"><?= esc((string) count($store['products'])) ?> karya</span>
        </div>

        <div class="lp-grid">
            <?php foreach ($store['products'] as $product): ?>
                <?= view('partials/product_card', ['product' => $product]) ?>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<div class="lp-split">
    <div>
        <?php if ($store['stories'] !== []): ?>
            <div class="lp-panel">
                <h2>Cerita dari sanggar</h2>
                <?php foreach ($store['stories'] as $story): ?>
                    <article style="margin-bottom:1rem;">
                        <?php if (! empty($story['image_path'])): ?>
                            <img src="<?= esc((string) url_gambar($story['image_path'])) ?>"
                                 alt="" style="border-radius:var(--lp-radius);width:100%;margin-bottom:.5rem;">
                        <?php endif; ?>
                        <strong><?= esc((string) $story['title']) ?></strong>
                        <?php if (! empty($story['subtitle'])): ?>
                            <p class="lp-small lp-muted"><?= esc((string) $story['subtitle']) ?></p>
                        <?php endif; ?>
                        <?php if (! empty($story['body'])): ?>
                            <p class="lp-small"><?= nl2br(esc((string) $story['body'])) ?></p>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($store['reviews'] !== []): ?>
            <div class="lp-panel">
                <h2>Ulasan pembeli</h2>
                <?php foreach ($store['reviews'] as $review): ?>
                    <div style="margin-bottom:.75rem;">
                        <strong><?= esc((string) ($review['customer_full_name'] ?? 'Pembeli')) ?></strong>
                        <?= bintang($review['rating'], null, false) ?>
                        <p class="lp-small"><?= esc((string) ($review['body'] ?? '')) ?></p>
                        <?php if (! empty($review['product_name'])): ?>
                            <small class="lp-muted">untuk <?= esc((string) $review['product_name']) ?></small>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <aside class="lp-aside-sticky">
        <div class="lp-panel">
            <h2>Jam usaha</h2>

            <table class="lp-table">
                <tbody>
                    <?php foreach ($hours as $hour): ?>
                        <tr>
                            <th scope="row" style="width:5.5rem;">
                                <?= esc($days[(string) $hour['day_of_week']] ?? '-') ?>
                                <?php if ((int) $hour['day_of_week'] === $today): ?>
                                    <span class="lp-muted">(hari ini)</span>
                                <?php endif; ?>
                            </th>
                            <td>
                                <?php if (! empty($hour['is_closed'])): ?>
                                    <span class="lp-muted">Tutup</span>
                                <?php else: ?>
                                    <?= esc(format_jam($hour['opens_at'])) ?>
                                    &ndash;
                                    <?= esc(format_jam($hour['closes_at'])) ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($store['facilities'] !== []): ?>
            <div class="lp-panel">
                <h2>Fasilitas</h2>
                <?php foreach ($store['facilities'] as $facility): ?>
                    <div class="lp-row" style="gap:.4rem;">
                        <span aria-hidden="true">&check;</span>
                        <span>
                            <?php
                            // `label` is a seller-editable name for the facility
                            // code, so an older row without one still renders.
                            echo esc((string) ($facility['label'] ?: $facility['facility']));
                            ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (auth()->loggedIn()): ?>
            <div class="lp-panel">
                <h2>Tanya sanggar ini</h2>
                <p class="lp-small lp-muted">
                    Buka salah satu karya untuk menanyakan bahan, ukuran, atau waktu pengerjaan.
                </p>
<?php if ($store['media'] !== []): ?>
    <section class="lp-section">
        <h2>Galeri sanggar</h2>
        <div class="lp-grid lp-grid--three">
            <?php foreach ($store['media'] as $media): ?>
                <figure class="lp-card__media" style="border-radius:var(--lp-radius);">
                    <?php if ($media['media_type'] === 'video'): ?>
                        <video controls preload="metadata" style="width:100%;aspect-ratio:4/3;object-fit:cover;">
                            <source src="<?= esc((string) url_gambar($media['file_path'])) ?>">
                        </video>
                    <?php else: ?>
                        <img src="<?= esc((string) url_gambar($media['thumbnail_path'] ?? $media['file_path'])) ?>"
                             alt="<?= esc((string) ($media['title'] ?? '')) ?>"
                             loading="lazy" style="width:100%;aspect-ratio:4/3;object-fit:cover;">
                    <?php endif; ?>
                    <?php if (! empty($media['caption'])): ?>
                        <figcaption class="lp-small lp-muted">
                            <?= esc((string) $media['caption']) ?>
                        </figcaption>
                    <?php endif; ?>
                </figure>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<?php if ($store['products'] !== []): ?>
                    <form method="post"
                          action="<?= route_to('chat_start', (int) $store['products'][0]['id']) ?>">
                        <?= csrf_field() ?>
                        <button class="lp-btn lp-btn--outline lp-btn--block" type="submit">
                            Mulai percakapan
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="lp-panel">
                <p><a href="<?= route_to('login') ?>">Masuk</a> untuk mengobrol dengan sanggar ini.</p>
            </div>
        <?php endif; ?>
    </aside>
</div>

<?= $this->endSection() ?>
