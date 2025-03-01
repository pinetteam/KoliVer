<?php
// app/Http/Controllers/BagisController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Statamic\Facades\Form;

class BagisController extends Controller
{
  public function istatistikler()
{
    $count = \DB::table('form_submissions')
        ->where('form', 'iyiligi_ulastir')
        ->count();
    
    // Toplam hesaplama...
    
    return view('bagis-istatistikleri', [
        'count' => $count,
        'total' => $total
    ]);
}
}