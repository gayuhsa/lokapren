<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\StoreModel;
use App\Services\StoreHours;

/**
 * Map-based locator for craft galleries and artisans around Magelang.
 */
class Locator extends BaseController
{
    public function index(): string
    {
        $model = model(StoreModel::class);
        $stores = $model
            ->select('id, name, slug, cover_url, address_line, subdistrict, city, lat, lng, opening_hours, verification_status')
            ->where('verification_status', 'verified')
            ->where('lat IS NOT NULL')
            ->where('lng IS NOT NULL')
            ->orderBy('name', 'ASC')
            ->findAll();

        $items = array_map(function (array $store): array {
            $hours = StoreHours::describe($store['opening_hours'] ?? null);

            return [
                'id'                 => (int) $store['id'],
                'name'               => (string) $store['name'],
                'slug'               => (string) $store['slug'],
                'cover_url'          => $store['cover_url'] === null ? null : (string) $store['cover_url'],
                'address_line'       => $store['address_line'] === null ? null : (string) $store['address_line'],
                'subdistrict'        => $store['subdistrict'] === null ? null : (string) $store['subdistrict'],
                'city'               => $store['city'] === null ? null : (string) $store['city'],
                'lat'                => (float) $store['lat'],
                'lng'                => (float) $store['lng'],
                'opening_hours'      => $store['opening_hours'] === null ? null : (string) $store['opening_hours'],
                'open_now'           => $hours['open'],
                'hours_label'        => $hours['label'],
                'hours_days'         => $hours['days'],
                'hours_range'        => $hours['hours'],
                'storefront_url'     => site_url('store/' . $store['slug']),
            ];
        }, $stores);

        return view('locator', [
            'stores' => $items,
        ]);
    }
}
