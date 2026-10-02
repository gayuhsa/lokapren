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
$routes->get('settings', '\App\Controllers\Settings::index', ['filter' => 'session']);
$routes->post('settings', '\App\Controllers\Settings::update', ['filter' => 'session']);

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
