<?php


namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Statamic\Facades\Entry;
use Statamic\Fields\Field;
use Statamic\Http\Controllers\CP\Fields\FieldtypeController;

class ProductOptionFieldController extends FieldtypeController
{
    public function index(Request $request)
    {
        $homePage = Entry::findBySlug('home', 'pages');

        if (!$homePage || !isset($homePage->get('urun_listesi')['products'])) {
            return response()->json([]);
        }

        $products = $homePage->get('urun_listesi')['products'];

        $options = [];
        foreach ($products as $product) {
            $options[] = [
                'value' => $product['product_code'],
                'label' => $product['product_name']
            ];
        }

        return response()->json($options);
    }
}
