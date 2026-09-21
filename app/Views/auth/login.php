<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login</title>
</head>
<body>
    <h1>Log in</h1>

    <?php if (session('error') !== null) : ?>
        <p><?= esc(session('error')) ?></p>
    <?php elseif (session('errors') !== null) : ?>
        <?php if (is_array(session('errors'))) : ?>
            <?php foreach (session('errors') as $error) : ?>
                <p><?= esc($error) ?></p>
            <?php endforeach ?>
        <?php else : ?>
            <p><?= esc(session('errors')) ?></p>
        <?php endif ?>
    <?php endif ?>

    <?php if (session('message') !== null) : ?>
        <p><?= esc(session('message')) ?></p>
    <?php endif ?>

    <form action="<?= url_to('login') ?>" method="post">
        <?= csrf_field() ?>

        <label for="email">Email</label>
        <br>
        <input type="email" id="email" name="email" inputmode="email" autocomplete="email" value="<?= old('email') ?>" required>
        <br><br>

        <label for="password">Password</label>
        <br>
        <input type="password" id="password" name="password" autocomplete="current-password" required>
        <br><br>

        <label>
            <input type="checkbox" name="remember"<?php if (old('remember')): ?> checked<?php endif ?>>
            Remember me
        </label>
        <br><br>

        <button type="submit">Log in</button>
    </form>

    <p>Don't have an account? <a href="<?= url_to('register') ?>">Register</a></p>
</body>
</html>