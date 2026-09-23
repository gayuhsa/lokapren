<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Home::index');

service('auth')->routes($routes, ['except' => ['register']]);

$routes->get('register', '\App\Controllers\Auth\RegisterController::registerView', ['as' => 'register']);
$routes->post('register', '\App\Controllers\Auth\RegisterController::registerAction');

$routes->get('marketplace', '\App\Controllers\Marketplace::index', ['filter' => 'session']);
$routes->get('product/(:segment)', '\App\Controllers\Product::show/$1', ['filter' => 'session']);
$routes->get('settings', '\App\Controllers\Settings::index', ['filter' => 'session']);
$routes->post('settings', '\App\Controllers\Settings::update', ['filter' => 'session']);

service('auth')->routes($routes);
