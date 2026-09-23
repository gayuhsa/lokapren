<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pengaturan Profil</title>
    <style>
        body { font-family: sans-serif; max-width: 30em; margin: 3rem auto; padding: 0 1rem; }
        h1 { font-size: 1.5rem; }
        .actions { display: flex; justify-content: space-between; align-items: center; margin-bottom: .5rem; }
        .back { color: #2563eb; text-decoration: none; }
        form { display: flex; flex-direction: column; gap: .6rem; }
        label { font-weight: bold; }
        input, select, button { font: inherit; padding: .5rem; border: 1px solid #aaa; border-radius: 4px; }
        button[type="submit"] { background: #2563eb; color: #fff; border: none; cursor: pointer; }
        button[type="submit"]:hover { background: #1d4ed8; }
        .notice { background: #fffbeb; border: 1px solid #f59e0b; color: #92400e; padding: .5rem; border-radius: 4px; }
        .alert { background: #fef2f2; border: 1px solid #f87171; color: #991b1b; padding: .5rem; border-radius: 4px; }
        .notice-temp { background: #e0f2fe; border: 1px solid #38bdf8; color: #075985; padding: .5rem; border-radius: 4px; }
    </style>
</head>
<body>
    <p class="notice-temp"><strong>Temporary placeholder frontend.</strong> This page will be replaced by a proper design.</p>

    <div class="actions">
        <a class="back" href="<?= esc(site_url('marketplace')) ?>">&larr; Marketplace</a>
    </div>
    <h1>Pengaturan Profil</h1>

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

    <form action="<?= esc(site_url('settings')) ?>" method="post">
        <?= csrf_field() ?>

        <label for="username">Username</label>
        <input type="text" id="username" name="username" autocomplete="username" value="<?= esc(old('username', $currentUsername)) ?>" required>

        <label for="email">Email</label>
        <input type="email" id="email" name="email" inputmode="email" autocomplete="email" value="<?= esc(old('email', $currentEmail)) ?>" required>

        <label for="role">Role</label>
        <select id="role" name="role">
            <option value="customer"<?= old('role', $currentRole) === 'customer' ? ' selected' : '' ?>>Customer</option>
            <option value="seller"<?= old('role', $currentRole) === 'seller' ? ' selected' : '' ?>>Seller</option>
        </select>

        <button type="submit">Simpan Perubahan</button>
    </form>
</body>
</html>