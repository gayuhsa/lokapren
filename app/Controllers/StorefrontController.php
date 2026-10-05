<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\SellerBusinessHourModel;
use App\Models\SellerDailyStatModel;

/**
 * A sanggar's public storefront.
 *
 * Reads only, and every query is scoped to the storefront's own user id, so one
 * seller can never see another's products, stories or hours.
 */
class StorefrontController extends BaseController
{
    public function show(string $slug)
    {
        $store = $this->catalogService->storefrontBySlug($slug);

        if ($store === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $sellerId = (int) $store['profile']['user_id'];

        (new SellerDailyStatModel())->increment($sellerId, date('Y-m-d'), [
            'visit_count' => 1,
        ]);

        return view('storefront/show', [
            'title'      => $store['profile']['display_name'],
            'store'      => $store,
            'hours'      => $this->orderedHours($store['hours']),
            'is_open'    => $store['is_open'],
            'cart_count' => $this->cartCount(),
        ]);
    }

    /**
     * Business hours in Monday-first order, which is how the design lists them.
     *
     * @param list<array<string, mixed>> $hours
     *
     * @return list<array<string, mixed>>
     */
    private function orderedHours(array $hours): array
    {
        $byDay = [];

        foreach ($hours as $hour) {
            $byDay[(int) $hour['day_of_week']] = $hour;
        }

        $ordered = [];

        foreach (range(1, 7) as $day) {
            $ordered[] = $byDay[$day] ?? [
                'day_of_week' => $day,
                'opens_at'   => null,
                'closes_at'  => null,
                'is_closed'  => 1,
            ];
        }

        return $ordered;
    }
}
