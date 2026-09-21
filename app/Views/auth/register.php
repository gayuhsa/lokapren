<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register</title>
</head>
<body>
    <h1>Create an account</h1>

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

    <form action="<?= url_to('register') ?>" method="post">
        <?= csrf_field() ?>

        <label for="email">Email</label>
        <br>
        <input type="email" id="email" name="email" inputmode="email" autocomplete="email" value="<?= old('email') ?>" required>
        <br><br>

        <label for="username">Username</label>
        <br>
        <input type="text" id="username" name="username" autocomplete="username" value="<?= old('username') ?>" required>
        <br><br>

        <label for="password">Password</label>
        <br>
        <input type="password" id="password" name="password" autocomplete="new-password" required>
        <br><br>

        <label for="password_confirm">Confirm password</label>
        <br>
        <input type="password" id="password_confirm" name="password_confirm" autocomplete="new-password" required>
        <br><br>

        <label for="role">I want to join as a</label>
        <br>
        <select id="role" name="role" required>
            <option value="">Select a role</option>
            <option value="customer"<?php if (old('role') === 'customer'): ?> selected<?php endif ?>>Customer</option>
            <option value="seller"<?php if (old('role') === 'seller'): ?> selected<?php endif ?>>Seller</option>
        </select>
        <br><br>

        <button type="submit">Register</button>
    </form>

    <p>Already have an account? <a href="<?= url_to('login') ?>">Log in</a></p>
</body>
</html>