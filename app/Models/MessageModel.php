<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Chat messages: text, image, voice note, product card or order card.
 *
 * `sender_id` is server-owned and must always be the authenticated Shield user.
 * Reading is gated through `ConversationModel::findForParticipant()`, never by
 * trusting a `conversation_id` from the request.
 */
class MessageModel extends BaseModel
{
    protected $table = 'messages';

    protected $returnType = 'array';

    /**
     * Only `created_at` exists — messages are immutable.
     */
    protected $useTimestamps = false;

    protected $useSoftDeletes = false;

    protected $allowedFields = [
        'conversation_id',
        'message_type',
        'body',
        'attachment_path',
        'attachment_name',
        'attachment_mime',
        'attachment_size',
    ];

    protected array $casts = [
        'conversation_id' => 'int',
        'sender_id'       => 'int',
        'attachment_size' => '?int',
    ];

    protected $validationRules = [
        'conversation_id'  => 'required|is_natural_no_zero',
        'message_type'     => 'required|in_list[text,image,voice,product_card,order_card]',
        'body'             => 'permit_empty|max_length[5000]',
        'attachment_path'  => 'permit_empty|max_length[255]',
        'attachment_name'  => 'permit_empty|max_length[150]',
        'attachment_mime'  => 'permit_empty|max_length[100]',
        'attachment_size'  => 'permit_empty|is_natural',
    ];

    /**
     * Message history for a thread, oldest first for easy rendering.
     *
     * @return list<array<string, mixed>>
     */
    public function forConversation(int $conversationId, int $limit = 50, int $offset = 0): array
    {
        $builder = $this->newQuery()
            ->where('conversation_id', $conversationId)
            ->orderBy('created_at', 'ASC')
            ->orderBy('id', 'ASC');

        return $this->newRows($builder, $limit, $offset);
    }

    /**
     * The most recent page of a thread, newest first (chat scrollback).
     *
     * @return list<array<string, mixed>>
     */
    public function latestForConversation(int $conversationId, int $limit = 50): array
    {
        $builder = $this->newQuery()
            ->where('conversation_id', $conversationId)
            ->orderBy('created_at', 'DESC')
            ->orderBy('id', 'DESC');

        return $this->newRows($builder, $limit);
    }

    /**
     * Mark every message the reader has not opened as read.
     *
     * `$conversationId` must already have been authorised by
     * `ConversationModel::findForParticipant()` — this method only narrows the
     * rows by reader, it does not check thread access.
     */
    public function markConversationRead(int $conversationId, int $readerId): int
    {
        return (int) $this->newQuery()
            ->where('conversation_id', $conversationId)
            ->where('sender_id !=', $readerId)
            ->where('read_at', null)
            ->set('read_at', date('Y-m-d H:i:s'))
            ->update();
    }
}