<?php

declare(strict_types=1);

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// ---------------------------------------------------------------------------
// Public
// ---------------------------------------------------------------------------

// The controller class is `HomeController`, so the handler is named explicitly
// rather than relying on the `Home::index` default-route shorthand.
$routes->get('/', 'HomeController::index', ['as' => 'home']);

$routes->get('catalog', 'CatalogController::index', ['as' => 'catalog']);

// The storefront and product detail pages are reachable by slug.
$routes->get('store/(:segment)', 'StorefrontController::show/$1', ['as' => 'storefront']);
$routes->get('product/(:segment)', 'CatalogController::product/$1', ['as' => 'product']);
$routes->get('location', 'LocatorController::index', ['as' => 'locator']);
$routes->get('articles', 'BlogController::index', ['as' => 'blog_index']);
$routes->get('articles/(:num)', 'BlogController::show/$1', ['as' => 'blog_show']);

// Uploads live in writable/, so they are streamed by a controller rather than
// served as static files. The route is public because storefront images appear
// on pages a guest can see.
$routes->get('media/(:any)', 'MediaController::show/$1', ['as' => 'media']);

// ---------------------------------------------------------------------------
// Authentication (CodeIgniter Shield)
// ---------------------------------------------------------------------------

// Shield's own routes are registered exactly once, with its register, login and
// magic-link pages excluded. Register and login are served by the controllers
// below so they can use Indonesian copy and, for login, accept an email *or* a
// username; magic link is excluded outright because the MVP authenticates with
// username/email + password only.
service('auth')->routes($routes, ['except' => ['register', 'login', 'magic-link']]);

$routes->get('register', '\App\Controllers\Auth\RegisterController::registerView', ['as' => 'register']);
$routes->post('register', '\App\Controllers\Auth\RegisterController::registerAction');
$routes->get('login', '\App\Controllers\Auth\LoginController::loginView', ['as' => 'login']);
$routes->post('login', '\App\Controllers\Auth\LoginController::loginAction');

// ---------------------------------------------------------------------------
// Signed-in customers and sellers
// ---------------------------------------------------------------------------

$routes->group('account', ['filter' => 'session'], static function (RouteCollection $routes): void {
    $routes->get('/', 'ProfileController::index', ['as' => 'profile']);
    $routes->post('profile', 'ProfileController::update', ['as' => 'profile_update']);

    // Address book. Geography is free text typed by the customer.
    $routes->get('addresses', 'AddressController::index', ['as' => 'addresses']);
    $routes->get('addresses/create', 'AddressController::create', ['as' => 'address_create']);
    $routes->post('addresses', 'AddressController::store', ['as' => 'address_store']);
    $routes->get('addresses/(:num)/edit', 'AddressController::edit/$1', ['as' => 'address_edit']);
    $routes->post('addresses/(:num)', 'AddressController::update/$1', ['as' => 'address_update']);
    $routes->post('addresses/(:num)/delete', 'AddressController::destroy/$1', ['as' => 'address_delete']);
    $routes->post('addresses/(:num)/default', 'AddressController::makeDefault/$1', ['as' => 'address_default']);
});

// Cart and checkout. One checkout may span several sellers; the service splits
// it into one order per seller.
$routes->group('cart', ['filter' => 'session'], static function (RouteCollection $routes): void {
    $routes->get('/', 'CartController::index', ['as' => 'cart']);
    $routes->post('add/(:num)', 'CartController::add/$1', ['as' => 'cart_add']);
    $routes->post('quantity', 'CartController::update', ['as' => 'cart_update']);
    $routes->post('remove/(:num)', 'CartController::remove/$1', ['as' => 'cart_remove']);
    $routes->post('clear', 'CartController::clear', ['as' => 'cart_clear']);
});

// The group's index is named `checkout_index` because a group and its index
// route cannot share a name — CodeIgniter would silently drop one of them.
$routes->group('checkout', ['filter' => 'session'], static function (RouteCollection $routes): void {
    $routes->get('/', 'CheckoutController::index', ['as' => 'checkout_index']);
    $routes->post('/', 'CheckoutController::store', ['as' => 'checkout_store']);
    $routes->get('success', 'CheckoutController::success', ['as' => 'checkout_success']);
});

$routes->group('orders', ['filter' => 'session'], static function (RouteCollection $routes): void {
    $routes->get('/', 'OrderController::index', ['as' => 'orders']);
    $routes->get('(:num)', 'OrderController::show/$1', ['as' => 'order_show']);
    $routes->post('(:num)/cancel', 'OrderController::cancel/$1', ['as' => 'order_cancel']);
    $routes->post('(:num)/chat', 'OrderController::createChat/$1', ['as' => 'order_chat']);
    $routes->get('(:num)/tracking', 'OrderController::tracking/$1', ['as' => 'order_tracking']);
});

// Reviews are only reachable from a delivered order, so the route always
// carries the order id and the service re-checks the purchase.
$routes->post('orders/(:num)/reviews', 'ReviewController::store/$1', ['filter' => 'session', 'as' => 'review_store']);

// In-app chat.
$routes->group('chat', ['filter' => 'session'], static function (RouteCollection $routes): void {
    $routes->get('/', 'ChatController::index', ['as' => 'chat_index']);
    $routes->get('(:num)', 'ChatController::thread/$1', ['as' => 'chat_thread']);
    $routes->post('(:num)', 'ChatController::send/$1', ['as' => 'chat_send']);
    $routes->post('start/(:num)', 'ChatController::startFromProduct/$1', ['as' => 'chat_start']);
    $routes->get('unread', 'ChatController::unread', ['as' => 'chat_unread']);
});

// ---------------------------------------------------------------------------
// Seller area
// ---------------------------------------------------------------------------

$routes->group('seller', ['filter' => ['session', 'role:seller']], static function (RouteCollection $routes): void {
    $routes->get('/', 'Seller\DashboardController::index', ['as' => 'seller_dashboard']);

    $routes->get('products', 'Seller\ProductController::index', ['as' => 'seller_products']);
    $routes->get('products/create', 'Seller\ProductController::create', ['as' => 'seller_product_create']);
    $routes->post('products', 'Seller\ProductController::store', ['as' => 'seller_product_store']);
    $routes->get('products/(:num)/edit', 'Seller\ProductController::edit/$1', ['as' => 'seller_product_edit']);
    $routes->post('products/(:num)', 'Seller\ProductController::update/$1', ['as' => 'seller_product_update']);
    $routes->post('products/(:num)/delete', 'Seller\ProductController::destroy/$1', ['as' => 'seller_product_delete']);
    $routes->post('products/(:num)/publish', 'Seller\ProductController::publish/$1', ['as' => 'seller_product_publish']);
    $routes->post('products/(:num)/images', 'Seller\ProductController::addImage/$1', ['as' => 'seller_product_image']);
    $routes->post('images/(:num)/delete', 'Seller\ProductController::deleteImage/$1', ['as' => 'seller_image_delete']);

    $routes->get('variants/(:num)', 'Seller\VariantController::index/$1', ['as' => 'seller_variants']);
    $routes->post('variants/(:num)', 'Seller\VariantController::store/$1', ['as' => 'seller_variant_store']);
    $routes->post('variants/(:num)/(:num)/delete', 'Seller\VariantController::destroy/$1/$2', ['as' => 'seller_variant_delete']);

    $routes->get('shop', 'Seller\ShopController::index', ['as' => 'seller_shop']);
    $routes->post('shop', 'Seller\ShopController::update', ['as' => 'seller_shop_update']);
    $routes->post('shop/hours', 'Seller\ShopController::updateHours', ['as' => 'seller_hours_update']);
    $routes->post('shop/facilities', 'Seller\ShopController::updateFacilities', ['as' => 'seller_facilities_update']);
    $routes->post('shop/story', 'Seller\ShopController::updateStory', ['as' => 'seller_story_update']);
    $routes->post('shop/media', 'Seller\ShopController::updateMedia', ['as' => 'seller_media_update']);
    $routes->post('shop/quick-replies', 'Seller\ShopController::storeQuickReply', ['as' => 'seller_quick_reply_store']);
    $routes->post('shop/quick-replies/(:num)/delete', 'Seller\ShopController::deleteQuickReply/$1', ['as' => 'seller_quick_reply_delete']);

    $routes->get('articles', 'Seller\BlogController::index', ['as' => 'seller_blogs']);
    $routes->get('articles/create', 'Seller\BlogController::create', ['as' => 'seller_blog_create']);
    $routes->post('articles', 'Seller\BlogController::store', ['as' => 'seller_blog_store']);
    $routes->get('articles/(:num)/edit', 'Seller\BlogController::edit/$1', ['as' => 'seller_blog_edit']);
    $routes->post('articles/(:num)', 'Seller\BlogController::update/$1', ['as' => 'seller_blog_update']);
    $routes->post('articles/(:num)/delete', 'Seller\BlogController::destroy/$1', ['as' => 'seller_blog_delete']);

    $routes->get('orders', 'Seller\OrderController::index', ['as' => 'seller_orders']);
    $routes->get('orders/(:num)', 'Seller\OrderController::show/$1', ['as' => 'seller_order_show']);
    $routes->post('orders/(:num)/status', 'Seller\OrderController::updateStatus/$1', ['as' => 'seller_order_status']);
});
