<?php
// app/Http/Controllers/ProductsController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Statamic\Facades\Entry;

class ProductsController extends Controller
{
    public function index()
    {
        $homePage = Entry::findBySlug('home', 'pages');

        if (!$homePage) {
            return response()->json(['error' => 'Home page not found'], 404);
        }

        $products = $homePage->get('urun_listesi')['products'] ?? [];

        return response()->json($products);
    }
}
