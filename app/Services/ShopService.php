<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SellerBusinessHourModel;
use App\Models\SellerFacilityModel;
use App\Models\SellerMediaModel;
use App\Models\SellerProfileModel;
use App\Models\SellerQuickReplyModel;
use App\Models\SellerStorySectionModel;

/**
 * A seller's storefront: identity, location, hours, facilities, story, media and
 * quick replies.
 *
 * AGENTS.md has no separate store entity, so everything here hangs off the
 * seller's own `user_id`. Every write resolves that id from the caller, and rows
 * carrying a `seller_id` are always written with it applied last, so a posted
 * value cannot redirect a row to another seller.
 *
 * Verification flags, rating averages and counters are deliberately absent from
 * every writable set here: there is no admin system that could set them, so they
 * are seeded or computed instead of edited.
 */
class ShopService
{
    private SellerProfileModel $profiles;
    private SellerBusinessHourModel $hours;
    private SellerFacilityModel $facilities;
    private SellerStorySectionModel $stories;
    private SellerMediaModel $media;
    private SellerQuickReplyModel $quickReplies;

    public function __construct()
    {
        $this->profiles     = new SellerProfileModel();
        $this->hours        = new SellerBusinessHourModel();
        $this->facilities   = new SellerFacilityModel();
        $this->stories      = new SellerStorySectionModel();
        $this->media        = new SellerMediaModel();
        $this->quickReplies = new SellerQuickReplyModel();
    }

    /**
     * Everything the seller's own storefront editor renders.
     *
     * @return array<string, mixed>|null
     */
    public function editorFor(int $sellerId): ?array
    {
        $profile = $this->profiles->newRow(
            $this->profiles->newQuery()->where('user_id', $sellerId)
        );

        if ($profile === null) {
            return null;
        }

        return [
            'profile'      => $profile,
            'hours'        => $this->hours->ownedBy($sellerId)->findAll(),
            'facilities'   => $this->facilities->ownedBy($sellerId)->findAll(),
            'stories'      => $this->stories->activeFor($sellerId),
            'media'        => $this->media->publishedFor($sellerId),
            'quick_replies' => $this->quickReplies->activeFor($sellerId),
            'facility_choices' => $this->facilities->availableFacilities(),
            'is_open'      => (new CatalogService())->isOpenNow($sellerId),
        ];
    }

    /**
     * Update the storefront identity and location.
     *
     * Geography is free text; `latitude`/`longitude` are optional and are only
     * kept when both halves are supplied, so a half-typed pin cannot put a
     * marker in the ocean.
     *
     * @param array<string, mixed> $data
     *
     * @return array{ok: bool, message: string}
     */
    public function saveProfile(int $sellerId, array $data): array
    {
        $profile = $this->profiles->newRow($this->profiles->newQuery()->where('user_id', $sellerId));

        if ($profile === null) {
            return ['ok' => false, 'message' => 'Profil sanggar belum tersedia.'];
        }

        $latitude  = $this->nullableFloat($data['latitude'] ?? null);
        $longitude = $this->nullableFloat($data['longitude'] ?? null);
        $hasPin    = $latitude !== null && $longitude !== null;

        $set = [
            'display_name'        => trim((string) ($data['display_name'] ?? $profile['display_name'])),
            'owner_name'          => $this->text($data['owner_name'] ?? null) ?? $profile['owner_name'],
            'tagline'             => $this->text($data['tagline'] ?? null),
            'description'         => $this->text($data['description'] ?? null),
            'craft_focus'         => $this->text($data['craft_focus'] ?? null),
            'address_line'        => $this->text($data['address_line'] ?? null),
            'village'             => $this->text($data['village'] ?? null),
            'district'            => $this->text($data['district'] ?? null),
            'regency'             => $this->text($data['regency'] ?? null),
            'province'            => $this->text($data['province'] ?? null),
            'postal_code'         => $this->text($data['postal_code'] ?? null),
            'landmark_name'       => $this->text($data['landmark_name'] ?? null),
            'landmark_distance_km' => $this->nullableFloat($data['landmark_distance_km'] ?? null),
            'latitude'            => $hasPin ? $latitude : null,
            'longitude'           => $hasPin ? $longitude : null,
            'is_active'           => ($data['is_active'] ?? true) ? 1 : 0,
            'updated_at'          => date('Y-m-d H:i:s'),
        ];

        // The slug is what makes the storefront addressable, so it follows the
        // display name the first time the seller picks one.
        if ($set['display_name'] !== '') {
            $set['slug'] = $this->uniqueSlug($this->slugify($set['display_name']), $sellerId);
        }

        $this->profiles->updateWhere($set, ['user_id' => $sellerId]);

        return ['ok' => true, 'message' => 'Profil sanggar diperbarui.'];
    }

    /**
     * Replace the weekly opening hours.
     *
     * The whole week is written in one transaction, so the storefront never
     * shows a half-saved schedule.
     *
     * @param array<int, array{opens_at: string|null, closes_at: string|null, is_closed: int}> $week
     *
     * @return array{ok: bool, message: string}
     */
    public function saveHours(int $sellerId, array $week): array
    {
        $rows = [];

        foreach (range(1, 7) as $day) {
            $entry = $week[$day] ?? null;

            $opens  = $this->timeOrNull($entry['opens_at'] ?? null);
            $closes = $this->timeOrNull($entry['closes_at'] ?? null);

            // A day with hours but no closing time, or a closing time at or
            // before opening, is treated as closed rather than stored as broken.
            $closed = (int) ($entry['is_closed'] ?? 0);

            if ($opens === null || $closes === null || $closes <= $opens) {
                $closed = 1;
                $opens  = $opens ?? '09:00:00';
                $closes = $closes ?? '18:00:00';

                if ($closes <= $opens) {
                    $closes = '18:00:00';
                }
            }

            $rows[] = [
                'day_of_week' => $day,
                'opens_at'    => $opens,
                'closes_at'   => $closes,
                'is_closed'   => $closed,
            ];
        }

        return $this->hours->replaceWeek($sellerId, $rows)
            ? ['ok' => true, 'message' => 'Jam buka diperbarui.']
            : ['ok' => false, 'message' => 'Jam buka gagal disimpan.'];
    }

    /**
     * Replace the seller's facility list.
     *
     * Only codes from `availableFacilities()` are accepted, so the storefront
     * filter cannot grow an arbitrary vocabulary.
     *
     * @param list<string> $facilities
     *
     * @return array{ok: bool, message: string}
     */
    public function saveFacilities(int $sellerId, array $facilities): array
    {
        $allowed = array_keys($this->facilities->availableFacilities());
        $db      = db_connect();

        $db->transStart();

        $this->facilities->newQuery()->where('seller_id', $sellerId)->delete();

        $position = 0;

        foreach (array_unique(array_map('strval', $facilities)) as $code) {
            if (! in_array($code, $allowed, true)) {
                continue;
            }

            $this->facilities->insertRow([
                'seller_id' => $sellerId,
                'facility'  => $code,
                'label'     => $this->facilities->availableFacilities()[$code],
                'position'  => $position++,
            ]);
        }

        if ($db->transStatus() === false) {
            $db->transRollback();

            return ['ok' => false, 'message' => 'Fasilitas gagal disimpan.'];
        }

        $db->transCommit();

        return ['ok' => true, 'message' => 'Fasilitas diperbarui.'];
    }

    /**
     * Add a story section to the storefront.
     *
     * @param array<string, mixed> $data
     *
     * @return array{ok: bool, message: string}
     */
    public function addStorySection(int $sellerId, array $data): array
    {
        $title = trim((string) ($data['title'] ?? ''));

        if ($title === '') {
            return ['ok' => false, 'message' => 'Judul cerita wajib diisi.'];
        }

        $position = (int) $this->stories->newQuery()->where('seller_id', $sellerId)->countAllResults();

        $this->stories->insertRow([
            'seller_id' => $sellerId,
            'title'     => $title,
            'subtitle'  => $this->text($data['subtitle'] ?? null),
            'body'      => $this->text($data['body'] ?? null),
            'image_path' => $this->text($data['image_path'] ?? null),
            'position'  => $position,
            'is_active' => 1,
        ]);

        return ['ok' => true, 'message' => 'Cerita sanggar ditambahkan.'];
    }

    /**
     * Update a story section the seller owns.
     *
     * @param array<string, mixed> $data
     *
     * @return array{ok: bool, message: string}
     */
    public function updateStorySection(int $sectionId, int $sellerId, array $data): array
    {
        if ($this->stories->newRow($this->stories->newQuery()
            ->where('id', $sectionId)
            ->where('seller_id', $sellerId)) === null) {
            return ['ok' => false, 'message' => 'Bagian cerita tidak ditemukan.'];
        }

        $this->stories->updateWhere([
            'title'      => trim((string) ($data['title'] ?? '')),
            'subtitle'   => $this->text($data['subtitle'] ?? null),
            'body'       => $this->text($data['body'] ?? null),
            'is_active'  => ($data['is_active'] ?? true) ? 1 : 0,
        ], ['id' => $sectionId, 'seller_id' => $sellerId]);

        return ['ok' => true, 'message' => 'Cerita sanggar diperbarui.'];
    }

    /**
     * Attach an uploaded photo or short video to the storefront.
     *
     * @return array{ok: bool, message: string}
     */
    public function addMedia(int $sellerId, string $mediaType, $file, ?string $title = null, ?string $caption = null): array
    {
        $kind = $mediaType === 'video' ? 'video' : 'image';

        $stored = (new UploadService())->store($file, 'sanggar', $kind);

        if ($stored === null) {
            return ['ok' => false, 'message' => 'Berkas tidak valid.'];
        }

        $position = (int) $this->media->newQuery()->where('seller_id', $sellerId)->countAllResults();

        $this->media->insertRow([
            'seller_id'   => $sellerId,
            'media_type'  => $kind,
            'title'       => $this->text($title),
            'caption'     => $this->text($caption),
            'file_path'   => $stored['path'],
            'thumbnail_path' => $stored['path'],
            'duration_seconds' => 0,
            'position'    => $position,
            'is_published' => 1,
        ]);

        return ['ok' => true, 'message' => 'Media ditambahkan.'];
    }

    /**
     * Remove one of the seller's media rows and its file.
     *
     * @return array{ok: bool, message: string}
     */
    public function deleteMedia(int $mediaId, int $sellerId): array
    {
        $row = $this->media->newRow($this->media->newQuery()
            ->where('id', $mediaId)
            ->where('seller_id', $sellerId));

        if ($row === null) {
            return ['ok' => false, 'message' => 'Media tidak ditemukan.'];
        }

        $this->media->newQuery()->where('id', $mediaId)->where('seller_id', $sellerId)->delete();

        (new UploadService())->delete($row['file_path']);
        (new UploadService())->delete($row['thumbnail_path']);

        return ['ok' => true, 'message' => 'Media dihapus.'];
    }

    /**
     * Save one of the seller's quick replies.
     *
     * @param array<string, mixed> $data
     *
     * @return array{ok: bool, message: string}
     */
    public function addQuickReply(int $sellerId, array $data): array
    {
        $body = trim((string) ($data['body'] ?? ''));

        if ($body === '') {
            return ['ok' => false, 'message' => 'Isi balasan cepat wajib diisi.'];
        }

        $position = (int) $this->quickReplies->newQuery()->where('seller_id', $sellerId)->countAllResults();

        $this->quickReplies->insertRow([
            'seller_id' => $sellerId,
            'title'     => $this->text($data['title'] ?? null) ?? 'Balasan cepat',
            'body'      => $body,
            'position'  => $position,
            'is_active' => 1,
        ]);

        return ['ok' => true, 'message' => 'Balasan cepat disimpan.'];
    }

    /**
     * Delete a quick reply the seller owns.
     *
     * @return array{ok: bool, message: string}
     */
    public function deleteQuickReply(int $replyId, int $sellerId): array
    {
        if ($this->quickReplies->newRow($this->quickReplies->newQuery()
            ->where('id', $replyId)
            ->where('seller_id', $sellerId)) === null) {
            return ['ok' => false, 'message' => 'Balasan cepat tidak ditemukan.'];
        }

        $this->quickReplies->newQuery()
            ->where('id', $replyId)
            ->where('seller_id', $sellerId)
            ->delete();

        return ['ok' => true, 'message' => 'Balasan cepat dihapus.'];
    }

    /**
     * A slug not already used by another sanggar.
     */
    private function uniqueSlug(string $base, int $sellerId): string
    {
        $base = $base === '' ? 'sanggar' : $base;
        $slug = $base;
        $n    = 2;

        while (true) {
            $existing = $this->profiles->newRow(
                $this->profiles->newQuery()
                    ->where('slug', $slug)
                    ->where('user_id !=', $sellerId)
            );

            if ($existing === null) {
                return $slug;
            }

            $slug = $base . '-' . $n;
            $n++;
        }
    }

    private function slugify(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';

        return trim($value, '-');
    }

    private function text(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function nullableFloat(mixed $value): ?float
    {
        if ($value === null || $value === '' || ! is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }

    /**
     * `HH:MM` from a form time input, normalised to `HH:MM:SS`.
     */
    private function timeOrNull(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $value = trim((string) $value);

        if (preg_match('/^(\d{1,2}):(\d{2})/', $value, $matches) !== 1) {
            return null;
        }

        $hour   = (int) $matches[1];
        $minute = (int) $matches[2];

        if ($hour > 23 || $minute > 59) {
            return null;
        }

        return sprintf('%02d:%02d:00', $hour, $minute);
    }
}
