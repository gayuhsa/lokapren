<?php

namespace App\Controllers;

class Marketplace extends BaseController
{
    public function index(): string
    {
        $request = $this->request;

        $category = (string) $request->getGet('category') ?: '';
        $sort     = (string) $request->getGet('sort') ?: '';
        $min      = $request->getGet('min');
        $max      = $request->getGet('max');

        $products = Product::catalog();

        $prices    = array_column($products, 'price');
        $scaleMin  = (int) floor(min($prices));
        $scaleMax  = (int) ceil(max($prices));

        if ($category !== '') {
            $products = array_values(array_filter(
                $products,
                static fn ($product) => $product['category'] === $category
            ));
        }

        $minFilter = is_numeric($min) ? (float) $min : null;
        $maxFilter = is_numeric($max) ? (float) $max : null;

        if ($minFilter !== null) {
            $products = array_values(array_filter(
                $products,
                static fn ($product) => $product['price'] >= $minFilter
            ));
        }

        if ($maxFilter !== null) {
            $products = array_values(array_filter(
                $products,
                static fn ($product) => $product['price'] <= $maxFilter
            ));
        }

        switch ($sort) {
            case 'price_asc':
                usort($products, static fn ($a, $b) => $a['price'] <=> $b['price']);
                break;
            case 'price_desc':
                usort($products, static fn ($a, $b) => $b['price'] <=> $a['price']);
                break;
            case 'rating_asc':
                usort($products, static fn ($a, $b) => $a['rating'] <=> $b['rating']);
                break;
            case 'rating_desc':
                usort($products, static fn ($a, $b) => $b['rating'] <=> $a['rating']);
                break;
        }

        return view('marketplace', [
            'products' => $products,
            'category' => $category,
            'sort'     => $sort,
            'min'      => $min ?? '',
            'max'      => $max ?? '',
            'scaleMin' => $scaleMin,
            'scaleMax' => $scaleMax,
        ]);
    }
}