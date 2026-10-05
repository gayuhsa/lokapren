<?php

declare(strict_types=1);

namespace App\Controllers;

/**
 * The store locator: a Leaflet/OpenStreetMap view of every sanggar that has
 * saved coordinates.
 *
 * Geography is free text, so the map works purely from `latitude`/`longitude`
 * and the search box matches names, crafts and places typed by the seller. A
 * sanggar without coordinates cannot appear, which is why the query requires
 * both columns.
 */
class LocatorController extends BaseController
{
    public function index()
    {
        $query = trim((string) $this->request->getGet('q'));

        $pins = $this->catalogService->locatorPins($query);

        // Markers are handed to the page as JSON so Leaflet can read them
        // without the view building the payload by hand.
        $markers = [];

        foreach ($pins as $pin) {
            $markers[] = [
                'id'       => (int) $pin['id'],
                'lat'      => (float) $pin['latitude'],
                'lon'      => (float) $pin['longitude'],
                'name'     => $pin['display_name'],
                'owner'    => $pin['owner_name'],
                'craft'    => $pin['craft_focus'],
                'place'    => $this->placeLabel($pin),
                'rating'   => (float) $pin['rating_average'],
                'reviews'  => (int) $pin['rating_count'],
                'verified' => (bool) $pin['is_verified'],
                'logo'     => url_gambar($pin['logo_path'] ?? null),
                'url'      => route_to('storefront', $pin['slug']),
            ];
        }

        return view('locator/index', [
            'title'      => 'Lokasi Pengrajin Magelang',
            'markers'    => $markers,
            'pins'       => $pins,
            'query'      => $query,
            'cart_count' => $this->cartCount(),
        ]);
    }

    /**
     * The free-text place a seller typed, joined for the pin popup.
     *
     * @param array<string, mixed> $pin
     */
    private function placeLabel(array $pin): string
    {
        return implode(', ', array_filter([
            $pin['village'] ?? null,
            $pin['district'] ?? null,
            $pin['regency'] ?? null,
        ], static fn ($part): bool => $part !== null && $part !== ''));
    }
}
