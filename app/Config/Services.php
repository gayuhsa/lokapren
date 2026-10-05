<?php

declare(strict_types=1);

namespace Config;

use CodeIgniter\Config\BaseService;

/**
 * Registry for the application's service layer.
 *
 * The marketplace services are shared per request, so a checkout that creates
 * four orders still builds a single `OrderModel` and a single database
 * connection. Each accessor also accepts `$getShared = false` for the rare case
 * where a caller needs a completely separate instance.
 *
 * NOTE — `getSharedInstance()` must be called with the name only:
 *
 *     static::getSharedInstance('cartService');       // correct
 *     static::getSharedInstance('cartService', X::class);   // recurses forever
 *
 * CI4's `getSharedInstance(string $key, ...$params)` appends `false` to
 * `$params` and forwards it, so passing the class makes `$getShared` receive
 * the class name instead of `false` — the accessor takes the shared branch
 * again, and the two calls call each other until memory runs out.
 */
class Services extends BaseService
{
    /**
     * @return \App\Services\CartService
     */
    public static function cartService($getShared = true)
    {
        return $getShared
            ? static::getSharedInstance('cartService')
            : new \App\Services\CartService();
    }

    /**
     * @return \App\Services\CheckoutService
     */
    public static function checkoutService($getShared = true)
    {
        return $getShared
            ? static::getSharedInstance('checkoutService')
            : new \App\Services\CheckoutService();
    }

    /**
     * @return \App\Services\OrderService
     */
    public static function orderService($getShared = true)
    {
        return $getShared
            ? static::getSharedInstance('orderService')
            : new \App\Services\OrderService();
    }

    /**
     * @return \App\Services\ChatService
     */
    public static function chatService($getShared = true)
    {
        return $getShared
            ? static::getSharedInstance('chatService')
            : new \App\Services\ChatService();
    }

    /**
     * @return \App\Services\CatalogService
     */
    public static function catalogService($getShared = true)
    {
        return $getShared
            ? static::getSharedInstance('catalogService')
            : new \App\Services\CatalogService();
    }

    /**
     * @return \App\Services\ProfileService
     */
    public static function profileService($getShared = true)
    {
        return $getShared
            ? static::getSharedInstance('profileService')
            : new \App\Services\ProfileService();
    }

    /**
     * @return \App\Services\UploadService
     */
    public static function uploadService($getShared = true)
    {
        return $getShared
            ? static::getSharedInstance('uploadService')
            : new \App\Services\UploadService();
    }

    /**
     * @return \App\Services\ProductService
     */
    public static function productService($getShared = true)
    {
        return $getShared
            ? static::getSharedInstance('productService')
            : new \App\Services\ProductService();
    }

    /**
     * @return \App\Services\ShopService
     */
    public static function shopService($getShared = true)
    {
        return $getShared
            ? static::getSharedInstance('shopService')
            : new \App\Services\ShopService();
    }
}
