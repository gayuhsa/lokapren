<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Home::index');

service('auth')->routes($routes, ['except' => ['register', 'logout']]);

$routes->get('register', '\App\Controllers\Auth\RegisterController::registerView', ['as' => 'register']);
$routes->post('register', '\App\Controllers\Auth\RegisterController::registerAction');

// The catalogue and product detail are public marketplace pages, so they must
// not carry Shield's `session` filter: that alias is Shield's SessionAuth,
// which sends every guest to the login form. Shield still starts its session
// service on demand, so session-dependent features keep working here.
$routes->get('marketplace', '\App\Controllers\Marketplace::index');
$routes->get('product/(:segment)', '\App\Controllers\Product::show/$1');
$routes->get('store/(:segment)', '\App\Controllers\Storefront::show/$1');
$routes->get('settings', '\App\Controllers\Settings::index', ['filter' => 'session']);
$routes->post('settings', '\App\Controllers\Settings::update', ['filter' => 'session']);

// Cart, checkout and orders all require a signed-in customer, and each one
// derives the acting user from Shield rather than from a submitted field. CSRF
// is already on globally (Security::$csrfProtection = 'session'), so the POST
// routes below cannot be reached by a forged cross-site form.
$routes->get('cart', '\App\Controllers\CartController::index', ['filter' => 'session']);
$routes->post('cart/add', '\App\Controllers\CartController::add', ['filter' => 'session']);
$routes->post('cart/line/(:num)', '\App\Controllers\CartController::update/$1', ['filter' => 'session']);
$routes->post('cart/line/(:num)/remove', '\App\Controllers\CartController::remove/$1', ['filter' => 'session']);

$routes->get('checkout', '\App\Controllers\CheckoutController::index', ['filter' => 'session']);
$routes->post('checkout/place', '\App\Controllers\CheckoutController::place', ['filter' => 'session']);
$routes->post('checkout/promo', '\App\Controllers\CheckoutController::promo', ['filter' => 'session']);

$routes->get('orders', '\App\Controllers\OrderController::index', ['filter' => 'session']);
$routes->get('order/(:num)', '\App\Controllers\OrderController::show/$1', ['filter' => 'session']);

$routes->get('chat', '\App\Controllers\ChatController::index', ['filter' => 'session']);
$routes->get('chat/tab/(:segment)', '\App\Controllers\ChatController::index/$1', ['filter' => 'session']);
$routes->get('chat/(:num)', '\App\Controllers\ChatController::show/$1', ['filter' => 'session']);
$routes->post('chat/(:num)/send', '\App\Controllers\ChatController::send/$1', ['filter' => 'session']);
$routes->post('chat/store/(:num)', '\App\Controllers\ChatController::open/$1', ['filter' => 'session']);

// Logging out is a state change, so it must not be reachable by following a
// GET link (an <img> or a prefetch would be enough to end someone's session).
// Shield only registers `logout` for GET, so it is excluded above and replaced
// here with a POST route that requires a CSRF token.
$routes->post(
    'logout',
    '\CodeIgniter\Shield\Controllers\LoginController::logoutAction',
    ['as' => 'logout', 'filter' => ['session', 'csrf']]
);

service('auth')->routes($routes, ['except' => ['logout']]);
