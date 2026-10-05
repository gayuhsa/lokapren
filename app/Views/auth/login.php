<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="lp-auth">
    <div class="lp-auth__card">
        <div class="lp-auth__head">
            <h1>Masuk ke Lokapren</h1>
            <p class="lp-muted">Temukan karya dari pengrajin Magelang dan sekitarnya.</p>
        </div>


        <form method="post" action="<?= route_to('login') ?>" autocomplete="on">
            <?= csrf_field() ?>

            <div class="lp-field">
                <label for="email">Email atau nama pengguna</label>
                <input id="email" type="text" name="email"
                       value="<?= esc(old('email')) ?>"
                       autocomplete="username"
                       inputmode="email"
                       required
                       autofocus>
            </div>

            <div class="lp-field">
                <label for="password">Kata sandi</label>
                <input id="password" type="password" name="password"
                       autocomplete="current-password"
                       required>
            </div>

            <label class="lp-field--inline" style="margin-bottom:1rem;">
                <input type="checkbox" name="remember" value="1" <?= old('remember') ? 'checked' : '' ?>>
                <span>Ingat saya di perangkat ini</span>
            </label>

            <button class="lp-btn lp-btn--solid lp-btn--block" type="submit">Masuk</button>
        </form>

        <p class="lp-small lp-muted" style="margin-top:1rem;text-align:center;">
            Belum punya akun?
            <a href="<?= route_to('register') ?>">Daftar sebagai pembeli atau pengrajin</a>
        </p>
    </div>
</div>

<?= $this->endSection() ?>
