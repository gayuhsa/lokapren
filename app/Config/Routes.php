<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Home::index');

service('auth')->routes($routes, ['except' => ['register', 'logout']]);

$routes->get('register', '\App\Controllers\Auth\RegisterController::registerView', ['as' => 'register']);
$routes->post('register', '\App\Controllers\Auth\RegisterController::registerAction');

$routes->get('marketplace', '\App\Controllers\Marketplace::index', ['filter' => 'session']);
$routes->get('product/(:segment)', '\App\Controllers\Product::show/$1', ['filter' => 'session']);
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
