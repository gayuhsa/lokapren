<?php

$title = 'Daftar Akun Baru';

echo view('partials/header', ['title' => $title, 'activeTab' => 'register']);

$error = session('error');
$errors = session('errors');
$fieldErrors = is_array($errors) ? $errors : [];
?>

<section class="auth-card" aria-labelledby="auth-title">
    <span class="auth-card__eyebrow">Akses Akun Digital</span>
    <h2 class="auth-card__title" id="auth-title">Daftar Akun Baru</h2>
    <p class="auth-card__lead">
        Buat akun untuk menjelajahi kurasi kriya adiluhung Magelang.
    </p>

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

    <nav class="tabs" aria-label="Pilih akses akun">
        <a class="tabs__item" href="<?= base_url('login') ?>">Masuk (Login)</a>
        <a class="tabs__item" href="<?= base_url('register') ?>" aria-current="page">Daftar Akun Baru</a>
    </nav>

    <form class="form" action="<?= base_url('register') ?>" method="post" novalidate>
        <?= csrf_field() ?>

        <div class="field<?= isset($fieldErrors['username']) ? ' field--invalid' : '' ?>">
            <div class="field__head">
                <label class="field__label" for="username">Nama Pengguna</label>
            </div>
            <div class="field__control">
                <input class="input" type="text" id="username" name="username" autocomplete="username"
                       placeholder="Nama yang akan tampil di Lokapren"
                       value="<?= old('username') ?>" required>
            </div>
            <?php if (isset($fieldErrors['username'])) : ?>
                <p class="field__error"><?= esc($fieldErrors['username']) ?></p>
            <?php endif ?>
        </div>

        <div class="field<?= isset($fieldErrors['email']) ? ' field--invalid' : '' ?>">
            <div class="field__head">
                <label class="field__label" for="email">Email</label>
            </div>
            <div class="field__control">
                <input class="input" type="email" id="email" name="email" inputmode="email"
                       autocomplete="email" placeholder="Masukkan email aktif"
                       value="<?= old('email') ?>" required>
            </div>
            <?php if (isset($fieldErrors['email'])) : ?>
                <p class="field__error"><?= esc($fieldErrors['email']) ?></p>
            <?php endif ?>
        </div>

        <div class="field<?= isset($fieldErrors['role']) ? ' field--invalid' : '' ?>">
            <div class="field__head">
                <label class="field__label" for="role">Daftar sebagai</label>
            </div>
            <div class="field__control">
                <select class="input" id="role" name="role" required>
                    <option value="">Pilih peran akun</option>
                    <option value="customer"<?= old('role') === 'customer' ? ' selected' : '' ?>>Pembeli (Customer)</option>
                    <option value="seller"<?= old('role') === 'seller' ? ' selected' : '' ?>>Pengrajin / Penjual (Seller)</option>
                </select>
            </div>
            <?php if (isset($fieldErrors['role'])) : ?>
                <p class="field__error"><?= esc($fieldErrors['role']) ?></p>
            <?php endif ?>
        </div>

        <div class="field<?= isset($fieldErrors['password']) ? ' field--invalid' : '' ?>">
            <div class="field__head">
                <label class="field__label" for="password">Password Akun</label>
            </div>
            <div class="field__control">
                <input class="input" type="password" id="password" name="password"
                       autocomplete="new-password" placeholder="Minimal 8 karakter" required>
            </div>
            <?php if (isset($fieldErrors['password'])) : ?>
                <p class="field__error"><?= esc($fieldErrors['password']) ?></p>
            <?php endif ?>
        </div>

        <div class="field<?= isset($fieldErrors['password_confirm']) ? ' field--invalid' : '' ?>">
            <div class="field__head">
                <label class="field__label" for="password_confirm">Ulangi Password</label>
            </div>
            <div class="field__control">
                <input class="input" type="password" id="password_confirm" name="password_confirm"
                       autocomplete="new-password" placeholder="Ketik ulang password" required>
            </div>
            <?php if (isset($fieldErrors['password_confirm'])) : ?>
                <p class="field__error"><?= esc($fieldErrors['password_confirm']) ?></p>
            <?php endif ?>
        </div>

        <button class="form__submit" type="submit">Daftar ke Lokapren</button>
    </form>

    <p class="form__alt">
        Sudah punya akun? <a href="<?= base_url('login') ?>">Masuk</a>
    </p>
</section>

<?php echo view('partials/footer'); ?>
