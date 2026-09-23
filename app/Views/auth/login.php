<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login</title>
    <style>
        body { font-family: sans-serif; max-width: 30em; margin: 3rem auto; padding: 0 1rem; }
        h1 { font-size: 1.5rem; }
        form { display: flex; flex-direction: column; gap: .6rem; }
        label { font-weight: bold; }
        input, button { font: inherit; padding: .5rem; border: 1px solid #aaa; border-radius: 4px; }
        button { background: #2563eb; color: #fff; border: none; cursor: pointer; }
        button:hover { background: #1d4ed8; }
        .notice { background: #fffbeb; border: 1px solid #f59e0b; color: #92400e; padding: .5rem; border-radius: 4px; }
        .alert { background: #fef2f2; border: 1px solid #f87171; color: #991b1b; padding: .5rem; border-radius: 4px; }
        .notice-temp { background: #e0f2fe; border: 1px solid #38bdf8; color: #075985; padding: .5rem; border-radius: 4px; }
    </style>
</head>
<body>
    <p class="notice-temp"><strong>Temporary placeholder frontend.</strong> This page will be replaced by a proper design.</p>
    <h1>Log in</h1>

    <?php if (session('error') !== null) : ?>
        <p class="alert"><?= esc(session('error')) ?></p>
    <?php elseif (session('errors') !== null) : ?>
        <?php if (is_array(session('errors'))) : ?>
            <?php foreach (session('errors') as $error) : ?>
                <p class="alert"><?= esc($error) ?></p>
            <?php endforeach ?>
        <?php else : ?>
            <p class="alert"><?= esc(session('errors')) ?></p>
        <?php endif ?>
    <?php endif ?>

    <?php if (session('message') !== null) : ?>
        <p class="notice"><?= esc(session('message')) ?></p>
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