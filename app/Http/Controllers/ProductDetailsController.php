
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Statamic\Facades\Entry;

class ProductDetailsController extends Controller
{
    public function getProductDetails($productCode)
    {
        $homePage = Entry::findBySlug('home', 'pages');

        if (!$homePage) {
            return response()->json(['error' => 'Home page not found'], 404);
        }

        $products = $homePage->get('urun_listesi')['products'] ?? [];

        foreach ($products as $product) {
            if ($product['product_code'] === $productCode) {
                return response()->json($product);
            }
        }

        return response()->json(['error' => 'Product not found'], 404);
    }
}
