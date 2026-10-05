<?php

declare(strict_types=1);

namespace App\Controllers\Seller;

use App\Controllers\BaseController;
use App\Models\SellerProfileModel;

/**
 * The seller's landing page in the Mitra area.
 *
 * The seller id is taken from Shield, never from the request, and every figure
 * is a server-side aggregate. A seller whose profile row is missing (an account
 * created before this table existed) is redirected to the shop editor rather
 * than shown a broken dashboard.
 */
class DashboardController extends BaseController
{
    public function index()
    {
        $sellerId = $this->requireUserId();

        $profile = (new SellerProfileModel())->findForUser($sellerId);

        if ($profile === null) {
            return redirect()->to(route_to('seller_shop'))
                ->with('error', 'Lengkapi data toko Anda terlebih dahulu.');
        }

        $stats = $this->catalogService->sellerDashboard($sellerId);
        $shop  = $this->shopService->editorFor($sellerId);

        return view('seller/dashboard', [
            'title'         => 'Dasbor Mitra',
            'profile'       => $stats['profile'] ?? null,
            'shop'          => $shop ?? [],
            'orders'        => $stats['orders'],
            'revenue_30d'   => $stats['revenue_30d'],
            'visit_count'   => $stats['visit_count'],
            'product_views' => $stats['product_view_count'],
            'chat_started'  => $stats['chat_started_count'],
            'geography'     => $stats['geography'],
            'unread_chat'   => $stats['unread_chat'],
            'low_stock'     => $stats['low_stock'],
            'product_count' => $this->productService->listForSeller($sellerId, 1)['total'],
            'story_count'   => count($shop['stories'] ?? []),
            'cart_count'    => $this->cartCount(),
        ]);
    }
}
