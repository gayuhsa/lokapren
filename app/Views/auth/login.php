<?php

$title = 'Masuk';

echo view('auth/partials/shell-start', ['title' => $title]);

$error = session('error');
$errors = session('errors');
$fieldErrors = is_array($errors) ? $errors : [];
?>

<section class="auth-card" aria-labelledby="auth-title">
    <span class="auth-card__eyebrow">Akses Akun Digital</span>
    <h2 class="auth-card__title" id="auth-title">Selamat Datang di Lokapren</h2>
    <p class="auth-card__lead">Akses kurasi kriya terbaik langsung dari sanggar budaya Magelang.</p>

    <?php if ($error !== null || $fieldErrors !== []) : ?>
        <ul class="notice-stack">
            <?php if ($error !== null) : ?>
                <li class="notice notice--error">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="9"/><path d="M12 7.5v5.5M12 16.3v.2"/>
                    </svg>
                    <span><?= esc($error) ?></span>
                </li>
            <?php endif ?>

            <?php foreach ($fieldErrors as $message) : ?>
                <li class="notice notice--error">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="9"/><path d="M12 7.5v5.5M12 16.3v.2"/>
                    </svg>
                    <span><?= esc($message) ?></span>
                </li>
            <?php endforeach ?>
        </ul>
    <?php endif ?>

    <?php if (session('message') !== null) : ?>
        <ul class="notice-stack">
            <li class="notice notice--info">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true">
                    <circle cx="12" cy="12" r="9"/><path d="m8.5 12.2 2.4 2.4 4.6-4.9"/>
                </svg>
                <span><?= esc(session('message')) ?></span>
            </li>
        </ul>
    <?php endif ?>

    <nav class="tabs" aria-label="Pilih akses akun">
        <a class="tabs__item" href="<?= base_url('login') ?>" aria-current="page">Masuk (Login)</a>
        <a class="tabs__item" href="<?= base_url('register') ?>">Daftar Akun Baru</a>
    </nav>

    <form class="form" action="<?= base_url('login') ?>" method="post" novalidate>
        <?= csrf_field() ?>

        <div class="field<?= isset($fieldErrors['email']) ? ' field--invalid' : '' ?>">
            <div class="field__head">
                <label class="field__label" for="email">Email</label>
            </div>
            <div class="field__control">
                <input class="input" type="email" id="email" name="email" inputmode="email"
                       autocomplete="email" placeholder="Masukkan email"
                       value="<?= old('email') ?>" required>
            </div>
            <?php if (isset($fieldErrors['email'])) : ?>
                <p class="field__error"><?= esc($fieldErrors['email']) ?></p>
            <?php endif ?>
        </div>

        <div class="field<?= isset($fieldErrors['password']) ? ' field--invalid' : '' ?>">
            <div class="field__head">
                <label class="field__label" for="password">Password Akun</label>
            </div>
            <div class="field__control">
                <input class="input" type="password" id="password" name="password"
                       autocomplete="current-password" placeholder="Ketik password Anda" required>
            </div>
            <?php if (isset($fieldErrors['password'])) : ?>
                <p class="field__error"><?= esc($fieldErrors['password']) ?></p>
            <?php endif ?>
        </div>

        <div class="form__meta">
            <label class="checkbox" for="remember">
                <input type="checkbox" id="remember" name="remember" value="1"<?= old('remember') ? ' checked' : '' ?>>
                <span>Ingat saya</span>
            </label>
        </div>

        <button class="form__submit" type="submit">Masuk ke Lokapren</button>
    </form>

    <p class="form__alt">
        Belum punya akun? <a href="<?= base_url('register') ?>">Buat Akun Baru</a>
    </p>
</section>

<?php echo view('auth/partials/shell-end'); ?>
