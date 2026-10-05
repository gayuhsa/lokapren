<?php

declare(strict_types=1);

use App\Models\ConversationModel;
use App\Models\MessageModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Tests\Support\Database\MarketplaceFixtures;

/**
 * Chat access controls: participants only, unread counters per side.
 *
 * @internal
 */
final class ChatAccessTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use MarketplaceFixtures;

    protected $namespace = null;

    public function testConversationOnlyAccessibleToParticipants(): void
    {
        $customerA = $this->makeUser('c-a');
        $customerB = $this->makeUser('c-b');
        $sellerA = $this->makeSeller('s-a')[0];
        $sellerB = $this->makeSeller('s-b')[0];
        $category = $this->makeCategory('Cat');
        [, $productA] = $this->makeProduct($sellerA, $category);

        $convos = new ConversationModel();

        $convId = $convos->firstOrCreateThread($customerA, $sellerA, $productA);
        $this->assertNotNull($convos->findForParticipant($convId, $customerA));
        $this->assertNotNull($convos->findForParticipant($convId, $sellerA));
        $this->assertNull($convos->findForParticipant($convId, $customerB));
        $this->assertNull($convos->findForParticipant($convId, $sellerB));
    }

    public function testUnreadCountersBumpForOtherParticipant(): void
    {
        $customer = $this->makeUser('c');
        $seller = $this->makeSeller('s')[0];
        $category = $this->makeCategory('Cat');
        [, $product] = $this->makeProduct($seller, $category);

        $convos = new ConversationModel();
        $messages = new MessageModel();

        $convId = $convos->firstOrCreateThread($customer, $seller, $product);
        $conv = $convos->find($convId);
        $this->assertSame(0, (int) $conv['customer_unread_count']);
        $this->assertSame(0, (int) $conv['seller_unread_count']);

        // Customer sends message -> seller's unread count increases, customer's does not.
        $messages->insertRow([
            'conversation_id' => $convId,
            'sender_id'       => $customer,
            'message_type'    => 'text',
            'body'            => 'Halo perajin',
            'created_at'      => date('Y-m-d H:i:s'),
        ]);
        $convos->touchLastMessage($convId, 'Halo perajin', fromCustomer: true);

        $conv = $convos->find($convId);
        $this->assertSame(0, (int) $conv['customer_unread_count']);
        $this->assertSame(1, (int) $conv['seller_unread_count']);

        // Seller replies -> customer's unread increases.
        $messages->insertRow([
            'conversation_id' => $convId,
            'sender_id'       => $seller,
            'message_type'    => 'text',
            'body'            => 'Halo! Bisa bantu',
            'created_at'      => date('Y-m-d H:i:s'),
        ]);
        $convos->touchLastMessage($convId, 'Halo! Bisa bantu', fromCustomer: false);

        $conv = $convos->find($convId);
        $this->assertSame(1, (int) $conv['customer_unread_count']);
        $this->assertSame(1, (int) $conv['seller_unread_count']);

        // Mark as read for seller clears seller side.
        $convos->markRead($convId, $seller);
        $conv = $convos->find($convId);
        $this->assertSame(1, (int) $conv['customer_unread_count']);
        $this->assertSame(0, (int) $conv['seller_unread_count']);

        // Mark as read for customer clears customer side.
        $convos->markRead($convId, $customer);
        $conv = $convos->find($convId);
        $this->assertSame(0, (int) $conv['customer_unread_count']);
        $this->assertSame(0, (int) $conv['seller_unread_count']);
    }

    public function testTotalUnreadForSumsAcrossConversations(): void
    {
        $customer = $this->makeUser('c');
        $seller = $this->makeSeller('s')[0];
        $seller2 = $this->makeSeller('s2')[0];
        $category = $this->makeCategory('Cat');
        [, $product1] = $this->makeProduct($seller, $category);
        [, $product2] = $this->makeProduct($seller2, $category);

        $convos = new ConversationModel();
        $messages = new MessageModel();

        $conv1 = $convos->firstOrCreateThread($customer, $seller, $product1);
        $conv2 = $convos->firstOrCreateThread($customer, $seller2, $product2);

        $messages->insertRow(['conversation_id' => $conv1, 'sender_id' => $customer, 'message_type' => 'text', 'body' => 'Hi', 'created_at' => date('Y-m-d H:i:s')]);
        $convos->touchLastMessage($conv1, 'Hi', true);
        $messages->insertRow(['conversation_id' => $conv2, 'sender_id' => $customer, 'message_type' => 'text', 'body' => 'Hi', 'created_at' => date('Y-m-d H:i:s')]);
        $convos->touchLastMessage($conv2, 'Hi', true);

        $this->assertSame(1, $convos->totalUnreadFor($seller));
        $this->assertSame(1, $convos->totalUnreadFor($seller2));
        $this->assertSame(0, $convos->totalUnreadFor($customer));

        $convos->markRead($conv1, $seller);
        $this->assertSame(0, $convos->totalUnreadFor($seller));
        $this->assertSame(1, $convos->totalUnreadFor($seller2));
    }
}
