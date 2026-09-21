<?php

namespace App\Controllers;

class Marketplace extends BaseController
{
    public function index(): string
    {
        return view('marketplace');
    }
}