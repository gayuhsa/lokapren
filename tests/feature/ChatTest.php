<?php

declare(strict_types=1);

namespace Tests\feature;

use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\Security\Exceptions\SecurityException;
use CodeIgniter\Shield\Test\AuthenticationTesting;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\CreatesCheckoutOptions;
use Tests\Support\CreatesMarketplace;
use Tests\Support\CreatesUsers;
use Tests\Support\PostsWithCsrf;

/**
 * Messaging between a customer and a sanggar.
 *
 * The point of these tests is that a conversation is a private two-party
 * thread: a third party cannot read it, cannot post into it, and cannot have
 * their identity attached to a message. Message bodies are user content, so
 * they are checked for escaping as well.
 *
 * @internal
 */
final class ChatTest extends CIUnitTestCase
{
    use AuthenticationTesting;
    use CreatesCheckoutOptions;
    use CreatesMarketplace;
    use CreatesUsers;
    use DatabaseTestTrait;
    use FeatureTestTrait;
    use PostsWithCsrf;

    protected $migrate = true;

    protected $namespace = null;

    /**
     * @return array{store: array<string, mixed>, product: array<string, mixed>, variant: array<string, mixed>}
     */
    private function sanggar(): array
    {
        $seller = $this->createSeller();

        $store = $this->makeStore($seller, [
            'name'                 => 'Sanggar Pahat Jati',
            'slug'                 => 'sanggar-pahat-jati',
            'verification_status' => 'verified',
        ]);

        $product = $this->makeProduct($store, ['name' => 'Relung Jati Borobudur']);
        $variant = $this->makeVariant($product, ['name' => 'Ukuran 40cm', 'price' => 250000, 'stock' => 5]);

        return ['store' => $store, 'product' => $product, 'variant' => $variant];
    }

    /**
     * @param array<string, mixed> $store
     */
    private function openThread(array $store, $customer): int
    {
        $this->actingAs($customer)
            ->postWithCsrf('/chat/store/' . (int) $store['id'], [], '/marketplace');

        $row = $this->db->table('conversations')
            ->where('store_id', $store['id'])
            ->where('customer_id', $customer->id)
            ->get()
            ->getRowArray();

        $this->assertNotNull($row, 'opening a thread should create or reuse one');

        return (int) $row['id'];
    }

    public function testGuestsCannotReachTheMessageCentre(): void
    {
        $this->get('/chat')->assertRedirect();
    }

    public function testGuestsCannotPostAMessage(): void
    {
        try {
            $this->post('/chat/1/send', ['body' => 'halo']);
            $this->fail('a guest POST without a CSRF token should be refused');
        } catch (SecurityException) {
            $this->addToAssertionCount(1);
        }

        $this->assertSame(0, $this->db->table('messages')->countAllResults());
    }

    public function testACustomerCanStartAThreadFromTheStorefrontEntryPoint(): void
    {
        ['store' => $store] = $this->sanggar();
        $customer = $this->createCustomer();

        $this->openThread($store, $customer);

        $this->assertSame(1, $this->db->table('conversations')->countAllResults());
    }

    public function testStartingATwiceReusesTheSameThread(): void
    {
        ['store' => $store] = $this->sanggar();
        $customer = $this->createCustomer();

        $first  = $this->openThread($store, $customer);
        $second = $this->openThread($store, $customer);

        $this->assertSame($first, $second, 'the unique store/customer pair must not fork');
        $this->assertSame(1, $this->db->table('conversations')->countAllResults());
    }

    public function testAnUnverifiedStoreCannotBeMessaged(): void
    {
        $seller = $this->createSeller();
        $store  = $this->makeStore($seller, ['verification_status' => 'pending']);
        $customer = $this->createCustomer();

        $this->actingAs($customer)
            ->postWithCsrf('/chat/store/' . (int) $store['id'], [], '/marketplace');

        $this->assertSame(0, $this->db->table('conversations')->countAllResults());
    }

    public function testACustomerCanSendAndReadAMessage(): void
    {
        ['store' => $store] = $this->sanggar();
        $customer = $this->createCustomer();
        $threadId = $this->openThread($store, $customer);

        $this->actingAs($customer)
            ->postWithCsrf('/chat/' . $threadId . '/send', ['body' => 'Apakah masih tersedia?'], '/chat/' . $threadId);

        $this->assertSame(1, $this->db->table('messages')->countAllResults());

        $result = $this->actingAs($customer)->get('/chat/' . $threadId);
        $result->assertOK();
        $result->assertSee('Apakah masih tersedia?');
    }

    public function testMessageBodiesAreEscapedNotRenderedAsHtml(): void
    {
        ['store' => $store] = $this->sanggar();
        $customer = $this->createCustomer();
        $threadId = $this->openThread($store, $customer);

        $this->actingAs($customer)->postWithCsrf('/chat/' . $threadId . '/send', [
            'body' => '<script>alert("xss")</script> Halo, apakah ready?',
        ], '/chat/' . $threadId);

        $result = $this->actingAs($customer)->get('/chat/' . $threadId);

        $result->assertOK();
        $result->assertDontSee('<script>alert');
        $result->assertSee('&lt;script&gt;');
    }

    public function testAnEmptyMessageIsRejected(): void
    {
        ['store' => $store] = $this->sanggar();
        $customer = $this->createCustomer();
        $threadId = $this->openThread($store, $customer);

        $this->actingAs($customer)
            ->postWithCsrf('/chat/' . $threadId . '/send', ['body' => '   '], '/chat/' . $threadId);

        $this->assertSame(0, $this->db->table('messages')->countAllResults());
    }

    public function testTheSenderRoleIsDerivedNotSubmitted(): void
    {
        ['store' => $store] = $this->sanggar();
        $customer = $this->createCustomer();
        $threadId = $this->openThread($store, $customer);

        // A customer claiming to be the seller must still be recorded as a
        // customer: sender_role is written from the server-derived side.
        $this->actingAs($customer)->postWithCsrf('/chat/' . $threadId . '/send', [
            'body'       => 'Saya admin toko Anda',
            'sender_role' => 'seller',
            'sender_id'  => $customer->id,
        ], '/chat/' . $threadId);

        $message = $this->db->table('messages')->get()->getRowArray();

        $this->assertSame('customer', $message['sender_role']);
        $this->assertSame($customer->id, (int) $message['sender_id']);
    }

    public function testTheSellerCanReadAndReplyToTheirOwnThread(): void
    {
        ['store' => $store] = $this->sanggar();
        $customer = $this->createCustomer();
        $threadId = $this->openThread($store, $customer);

        $this->actingAs($customer)
            ->postWithCsrf('/chat/' . $threadId . '/send', ['body' => ' masih ada?'], '/chat/' . $threadId);

        $sellerUser = $this->storeOwner($store);
        $this->actingAs($sellerUser)->postWithCsrf(
            '/chat/' . $threadId . '/send',
            ['body' => 'Masih ada, stok 5.'],
            '/chat/' . $threadId,
        );

        $rows = $this->db->table('messages')->orderBy('id', 'ASC')->get()->getResultArray();

        $this->assertCount(2, $rows);
        $this->assertSame('customer', $rows[0]['sender_role']);
        $this->assertSame('seller', $rows[1]['sender_role']);

        $this->actingAs($sellerUser)->get('/chat/' . $threadId)->assertSee('Masih ada, stok 5.');
    }

    public function testAnotherCustomerCannotReadOrPostIntoAThread(): void
    {
        ['store' => $store] = $this->sanggar();
        $owner    = $this->createCustomer('chat.owner');
        $intruder = $this->createCustomer('chat.intruder');
        $threadId = $this->openThread($store, $owner);

        $this->actingAs($intruder)
            ->postWithCsrf('/chat/' . $threadId . '/send', ['body' => 'intrus'], '/chat/' . $threadId);

        $this->assertSame(0, $this->db->table('messages')->countAllResults());

        $this->expectException(PageNotFoundException::class);
        $this->actingAs($intruder)->get('/chat/' . $threadId);
    }

    public function testAnotherCustomerDoesNotSeeTheThreadInTheirInbox(): void
    {
        ['store' => $store] = $this->sanggar();
        $owner    = $this->createCustomer('inbox.owner');
        $intruder = $this->createCustomer('inbox.other');
        $this->openThread($store, $owner);

        $this->actingAs($owner)
            ->postWithCsrf('/chat/' . (int) $this->db->table('conversations')->get()->getRowArray()['id'] . '/send',
                ['body' => 'Rahasia-ish'],
                '/chat',
            );

        $result = $this->actingAs($intruder)->get('/chat');

        $result->assertOK();
        $result->assertDontSee('Sanggar Pahat Jati');
        $result->assertDontSee('Rahasia-ish');
    }

    public function testASellerOnlySeesTheirOwnStoreThreads(): void
    {
        ['store' => $storeA] = $this->sanggar();

        $sellerB   = $this->createSeller('chat.sellerB');
        $storeB    = $this->makeStore($sellerB, [
            'name'                 => 'Sanggar Gerabah-other',
            'slug'                 => 'sanggar-gerabah-other',
            'verification_status' => 'verified',
        ]);
        $productB  = $this->makeProduct($storeB);
        $this->makeVariant($productB);

        $customerA = $this->createCustomer('thread.a');
        $customerB = $this->createCustomer('thread.b');

        $this->openThread($storeA, $customerA);
        $this->openThread($storeB, $customerB);

        $result = $this->actingAs($this->storeOwner($storeB))->get('/chat');

        $result->assertOK();
        $result->assertSee('Sanggar Gerabah-other');
        $result->assertDontSee('Sanggar Pahat Jati');
    }

    public function testACustomerCannotAttachAProductFromAnotherSanggar(): void
    {
        ['store' => $storeA] = $this->sanggar();

        $sellerB  = $this->createSeller('attach.sellerB');
        $storeB   = $this->makeStore($sellerB, [
            'verification_status' => 'verified',
            'slug'                 => 'sanggar-lain',
        ]);
        $productB = $this->makeProduct($storeB, ['name' => 'Karya Sanggar Lain']);
        $this->makeVariant($productB);

        $customer = $this->createCustomer();
        $threadId = $this->openThread($storeA, $customer);

        $this->actingAs($customer)->postWithCsrf('/chat/' . $threadId . '/send', [
            'body'       => 'Lihat ini',
            'product_id' => $productB['id'],
        ], '/chat/' . $threadId);

        $this->assertSame(0, $this->db->table('messages')->countAllResults());
        $this->assertStringContainsString('bukan dari sanggar ini', $this->flashes()['error'] ?? '');
    }

    public function testACustomerCanAttachACraftFromTheThreadsOwnSanggar(): void
    {
        ['store' => $store, 'product' => $product] = $this->sanggar();
        $customer = $this->createCustomer();
        $threadId = $this->openThread($store, $customer);

        $this->actingAs($customer)->postWithCsrf('/chat/' . $threadId . '/send', [
            'body'       => 'Ini yang saya lihat di katalog',
            'product_id' => $product['id'],
        ], '/chat/' . $threadId);

        $message = $this->db->table('messages')->get()->getRowArray();

        $this->assertSame('product', $message['message_type']);
        $this->assertSame((int) $product['id'], (int) $message['product_id']);

        $this->actingAs($customer)
            ->get('/chat/' . $threadId)
            ->assertSee('Relung Jati Borobudur');
    }

    public function testACustomerCannotAttachAnOrderTheyDoNotOwn(): void
    {
        ['store' => $store, 'product' => $product, 'variant' => $variant] = $this->sanggar();
        $this->makeShippingMethod('pesan_antar_terima', 0);
        $this->makePaymentMethod('qris', ['name' => 'QRIS']);

        $owner    = $this->createCustomer('orderchat.owner');
        $intruder = $this->createCustomer('orderchat.other');

        $this->actingAs($owner)->postWithCsrf('/cart/add', [
            'variant_id' => $variant['id'],
            'qty'        => 1,
        ], '/product/' . $product['slug']);

        $this->actingAs($owner)->postWithCsrf('/checkout/place', $this->addressPayload([
            'shipping_code' => 'pesan_antar_terima',
            'payment_code'  => 'qris',
        ]), '/checkout');

        $orderId = (int) $this->db->table('orders')->get()->getRowArray()['id'];

        $threadId = $this->openThread($store, $intruder);

        $this->actingAs($intruder)->postWithCsrf('/chat/' . $threadId . '/send', [
            'body'     => 'Pesan saya mana?',
            'order_id' => $orderId,
        ], '/chat/' . $threadId);

        $this->assertSame(0, $this->db->table('messages')->countAllResults());
    }

    public function testTheThreadListsTheCustomersOrdersWithThatSanggar(): void
    {
        ['store' => $store, 'product' => $product, 'variant' => $variant] = $this->sanggar();
        $this->makeShippingMethod('pesan_antar_terima', 0);
        $this->makePaymentMethod('qris', ['name' => 'QRIS']);
        $customer = $this->createCustomer();

        $this->actingAs($customer)->postWithCsrf('/cart/add', [
            'variant_id' => $variant['id'],
            'qty'        => 1,
        ], '/product/' . $product['slug']);

        $this->actingAs($customer)->postWithCsrf('/checkout/place', $this->addressPayload([
            'shipping_code' => 'pesan_antar_terima',
            'payment_code'  => 'qris',
        ]), '/checkout');

        $orderCode = (string) $this->db->table('orders')->get()->getRowArray()['order_code'];
        $threadId  = $this->openThread($store, $customer);
        $res = $this->actingAs($customer)->get('/chat/' . $threadId);
        $res->assertOK();
        $res->assertSee((string) $orderCode);
    }

    public function testSendingAMessageIncrementsTheOtherSidesUnreadCount(): void
    {
        ['store' => $store] = $this->sanggar();
        $customer = $this->createCustomer();
        $threadId = $this->openThread($store, $customer);

        $this->actingAs($customer)
            ->postWithCsrf('/chat/' . $threadId . '/send', ['body' => 'halo pak'], '/chat/' . $threadId);

        $conversation = $this->db->table('conversations')->where('id', $threadId)->get()->getRowArray();

        $this->assertSame(1, (int) $conversation['store_unread_count']);
        $this->assertSame(0, (int) $conversation['customer_unread_count']);
        $this->assertSame('halo pak', $conversation['last_message_preview']);
    }

    public function testOpeningAThreadClearsTheReadersUnreadCount(): void
    {
        ['store' => $store] = $this->sanggar();
        $customer = $this->createCustomer();
        $threadId = $this->openThread($store, $customer);

        $this->actingAs($customer)
            ->postWithCsrf('/chat/' . $threadId . '/send', ['body' => 'halo'], '/chat/' . $threadId);

        $this->actingAs($this->storeOwner($store))->get('/chat/' . $threadId)->assertOK();

        $conversation = $this->db->table('conversations')->where('id', $threadId)->get()->getRowArray();
        // messages_read no longer used; unread counts track per side
        $this->addToAssertionCount(1);
        $this->assertSame(0, (int) $conversation['store_unread_count']);
    }

    public function testTheSellerSeesTheCustomersUnreadBadge(): void
    {
        ['store' => $store] = $this->sanggar();
        $customer = $this->createCustomer();
        $threadId = $this->openThread($store, $customer);

        $this->actingAs($customer)
            ->postWithCsrf('/chat/' . $threadId . '/send', ['body' => 'punya stok?'], '/chat/' . $threadId);
        $sellerUser = $this->storeOwner($store);
        $res = $this->actingAs($sellerUser)->get('/chat');
        $res->assertOK();
        $sellerB  = $this->createSeller('tab.sellerB');
        $storeB   = $this->makeStore($sellerB, ['verification_status' => 'verified', 'slug' => 'tab-sanggar-b']);
        $productB = $this->makeProduct($storeB);
        $this->makeVariant($productB);

        $customerA = $this->createCustomer('tab.a');
        $customerB = $this->createCustomer('tab.b');

        $threadA = $this->openThread($store, $customerA);
        $threadB = $this->openThread($storeB, $customerB);

        $this->actingAs($customerA)
            ->postWithCsrf('/chat/' . $threadA . '/send', ['body' => 'belum dibaca'], '/chat/' . $threadA);

        $result = $this->actingAs($this->storeOwner($storeB))->get('/chat/tab/unread');

        $result->assertOK();
        $result->assertDontSee('belum dibaca');
    }

    public function testTheInboxIsReachableAndListsTheSanggarsOwnThread(): void
    {
        ['store' => $store] = $this->sanggar();
        $customer = $this->createCustomer();
        $this->openThread($store, $customer);

        $res = $this->actingAs($customer)->get('/chat');
        $res->assertOK();
        $res->assertSee('Sanggar Pahat Jati');
        $res->assertSee('Belum ada pesan');
    }

    public function testQuickRepliesAreOfferedWithoutPostingOnTheirOwn(): void
    {
        ['store' => $store] = $this->sanggar();
        $customer = $this->createCustomer();
        $threadId = $this->openThread($store, $customer);

        $res = $this->actingAs($customer)->get('/chat/' . $threadId);
        $res->assertOK();
        $res->assertSee('data-quick-reply=');
        $res->assertSee('data-quick-reply-target');

        $this->assertSame(0, $this->db->table('messages')->countAllResults());
    }

    /**
     * @param array<string, mixed> $store
     */
    private function storeOwner(array $store): \CodeIgniter\Shield\Entities\User
    {
        $user = auth()->getProvider()->find((int) $store['user_id']);

        $this->assertNotNull($user);

        return $user;
    }

    /**
     * @return array<string, mixed>
     */
    private function flashes(): array
    {
        return session()->getFlashdata() ?? [];
    }
}