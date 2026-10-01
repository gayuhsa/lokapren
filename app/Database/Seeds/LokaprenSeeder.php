<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Models\UserModel;

/**
 * Base class for Lokapren seeders.
 *
 * Adds the things every seeder in this project needs:
 *
 * 1. `upsertUser()`, which creates Shield users correctly. Shield's
 *    UserModel::save() returns a bool and does NOT backfill the generated id
 *    onto the entity passed to it, so a naive `$user->addGroup()` throws a
 *    NOT NULL violation on auth_groups_users.user_id. This helper reads the
 *    insert id back explicitly.
 *
 * 2. `upsertRow()` / `idFor()`, so seeding is idempotent and each seeder can
 *    resolve what an earlier seeder created. Values are looked up by natural
 *    key in the database rather than held in memory, because `call()` builds a
 *    fresh seeder instance and instance state would not survive the hop.
 */
abstract class LokaprenSeeder extends Seeder
{
    /**
     * Creates or reuses a Shield user with the given groups.
     *
     * Idempotent: re-running returns the existing user instead of failing on
     * the unique email constraint.
     *
     * @param list<string> $groups
     */
    protected function upsertUser(string $email, string $password, array $groups = []): User
    {
        $provider = new UserModel();

        // Email is stored in auth_identities, not users.username, so look the
        // account up the same way Shield's login flow does.
        $existing = $provider->findByCredentials(['email' => $email]);

        if ($existing !== null) {
            return $this->repairUser($existing, $password, $groups);
        }

        $user = new User([
            'username' => $email,
            'email'    => $email,
            'password' => $password,
            'active'   => 1,
        ]);

        // save() returns bool and leaves $user->id null on this Shield version.
        $provider->skipValidation(true)->save($user);

        $errors = $provider->errors();

        if ($errors !== []) {
            throw new \RuntimeException('Could not create seeder user: ' . json_encode($errors));
        }

        // Read the generated id back onto the entity before touching groups.
        $user->id = (int) $provider->getInsertID();

        foreach ($groups as $group) {
            $user->addGroup($group);
        }

        return $user;
    }

    /**
     * Brings an already-present demo account back to a known, usable state.
     *
     * Seeding must be the source of truth for demo credentials, otherwise a
     * re-run leaves whatever password the row happens to hold and nobody can
     * log in. Password, activation and groups are all made to match.
     *
     * @param list<string> $groups
     */
    private function repairUser(User $user, string $password, array $groups): User
    {
        $changed = false;

        $currentHash = $user->getEmailIdentity()?->secret2;

        if (! is_string($currentHash) || ! password_verify($password, $currentHash)) {
            // Assigning the plain password makes the entity re-hash it into
            // auth_identities.secret2 on save.
            $user->password = $password;
            $changed        = true;
        }

        if ((int) $user->active !== 1) {
            $user->active = 1;
            $changed      = true;
        }

        if ($changed) {
            (new UserModel())->skipValidation(true)->save($user);
        }

        $wanted = array_values(array_unique($groups));
        $actual = $user->getGroups() ?? [];

        sort($wanted);
        sort($actual);

        if ($wanted !== $actual) {
            $user->syncGroups($wanted);
        }

        return $user;
    }

    /**
     * Inserts a row, or updates it when the natural key already exists.
     *
     * Seeding is an upsert rather than a truncate so demo rows keep the ids
     * they already have. That matters because products point at stores: wiping
     * and reinserting would renumber stores and orphan the catalogue.
     *
     * @param array<string, mixed> $data
     */
    protected function upsertRow(string $table, string $keyColumn, string $keyValue, array $data): int
    {
        $data[$keyColumn] = $keyValue;

        $existing = $this->db->table($table)->where($keyColumn, $keyValue)->get()->getRow();

        if ($existing !== null) {
            $this->db->table($table)->where($keyColumn, $keyValue)->update($this->withTimestamps($table, $data));

            return (int) $existing->id;
        }

        $this->db->table($table)->insert($this->withTimestamps($table, $data));

        return (int) $this->db->insertID();
    }

    /**
     * Resolves a row id from its natural key.
     *
     * Seeders look each other's rows up here rather than passing ids around,
     * so they can be run individually in any order as long as dependencies
     * already exist.
     */
    protected function idFor(string $table, string $keyColumn, string $keyValue): int
    {
        $row = $this->db->table($table)->where($keyColumn, $keyValue)->get()->getRow();

        if ($row === null) {
            throw new \RuntimeException(
                "Seeder looked up {$table}.{$keyColumn} = '{$keyValue}' and found nothing. "
                . 'Run the seeder that creates it first.'
            );
        }

        return (int) $row->id;
    }

    /**
     * Fills created_at/updated_at, which are NOT NULL with no column default.
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    private function withTimestamps(string $table, array $data): array
    {
        if (! array_key_exists('created_at', $data)) {
            $data['created_at'] = $this->now();
        }

        $data['updated_at'] = $this->now();

        return $data;
    }

    protected function now(): string
    {
        return date('Y-m-d H:i:s');
    }

    /**
     * Progress line for interactive runs. Seeder::call() already honours
     * $silent, but our own printf() calls would otherwise leak into test
     * output, so they route through here.
     */
    protected function report(string $line): void
    {
        if ($this->silent) {
            return;
        }

        echo $line . PHP_EOL;
    }
}
