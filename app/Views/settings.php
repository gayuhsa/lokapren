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
        .hint { margin: -.2rem 0 0; font-size: .85rem; color: #555; }
        input[disabled] { background: #f3f4f6; color: #555; }
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

        <?php if ($canManageRoles) : ?>
            <label for="role">Role</label>
            <select id="role" name="role">
                <?php foreach ($assignableRoles as $roleValue => $roleTitle) : ?>
                    <option value="<?= esc($roleValue, 'attr') ?>"<?= old('role', $currentRole) === $roleValue ? ' selected' : '' ?>><?= esc($roleTitle) ?></option>
                <?php endforeach ?>
            </select>
            <p class="hint">Anda memiliki izin <code>users.manage</code> sehingga dapat mengubah role akun ini.</p>
        <?php else : ?>
            <label for="role">Role</label>
            <input type="text" id="role" value="<?= esc($currentRole) ?>" disabled>
            <p class="hint">Role hanya dapat diubah oleh administrator yang memiliki izin <code>users.manage</code>.</p>
        <?php endif ?>

        <button type="submit">Simpan Perubahan</button>
    </form>
</body>
</html>