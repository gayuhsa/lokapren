<?php

declare(strict_types=1);

namespace App\Database\Seeds;

/**
 * Ensures the demo accounts exist with the right Shield groups.
 *
 * Idempotent: accounts are matched on email and reused, so re-running is safe.
 * Shield tables are only ever written through Shield's own API.
 *
 * Run with: php spark db:seed UserSeeder
 */
class UserSeeder extends LokaprenSeeder
{
    /**
     * Development-only password shared by every demo account.
     *
     * @var string
     */
    public const DEMO_PASSWORD = 'Lokapren123!';

    public function run(): void
    {
        $accounts = [
            ['admin@lokapren.test', ['admin']],
            ['klipoh@lokapren.test', ['seller']],
            ['muntilan@lokapren.test', ['seller']],
            ['candirejo@lokapren.test', ['seller']],
            ['borobudur@lokapren.test', ['seller']],
            ['andi@lokapren.test', ['customer']],
            ['dewi@lokapren.test', ['customer']],
            ['rina@lokapren.test', ['customer']],
        ];

        foreach ($accounts as [$email, $groups]) {
            $user = $this->upsertUser($email, self::DEMO_PASSWORD, $groups);

            $this->report(sprintf('  %-28s id=%-3d [%s]', $email, $user->id, implode(', ', $groups)));
        }

        $this->report('');
        $this->report('  demo password: ' . self::DEMO_PASSWORD);
        $this->report('  demo only - never use these accounts in production.');
    }
}
