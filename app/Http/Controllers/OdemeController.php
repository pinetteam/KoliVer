<?php

namespace App\Http\Controllers;

use App\Services\VakifBankOdemeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Statamic\Facades\Session;

class OdemeController extends Controller
{
    protected $odemeService;

    public function __construct(VakifBankOdemeService $odemeService)
    {
        $this->odemeService = $odemeService;
    }

    public function odeme(Request $request)
    {
        // Sepet verilerini al
        $cartData = json_decode($request->input('cart_data'), true);

        if (empty($cartData)) {
            Log::warning('Boş sepet ile ödeme sayfası açılma girişimi');
            return redirect('/')->with('error', 'Sepetiniz boş.');
        }

        // Sepet toplamını hesapla
        $cartTotal = 0;
        foreach ($cartData as $item) {
            $cartTotal += $item['price'] * $item['quantity'];
        }

        Log::info('Ödeme sayfası başlatıldı', [
            'cart_data_count' => count($cartData),
            'cart_total' => $cartTotal
        ]);

        // Sepet verilerini session'a kaydet
        session(['cart_data' => $cartData, 'cart_total' => $cartTotal]);

        // Ödeme sayfasını göster
        return view('odeme', [
            'cartData' => $cartData,
            'cartTotal' => $cartTotal
        ]);
    }

    public function odemeTamamla(Request $request)
    {
        // Form verilerini doğrula
        $validated = $request->validate([
            'ad_soyad' => 'required|string|max:255',
            'telefon' => 'required|string|max:20',
            'sehir' => 'required|string|max:50',
            'tc_kimlik' => 'nullable|string|size:11',
            'dogum_tarihi' => 'nullable|date',
            'kart_no' => 'required|string|min:15|max:19',
            'kart_ay' => 'required|string|size:2',
            'kart_yil' => 'required|string|size:4',
            'kart_cvv' => 'required|string|min:3|max:4',
        ]);

        Log::info('Ödeme tamamlama başlatıldı', [
            'ad_soyad' => $validated['ad_soyad'],
            'telefon' => $validated['telefon']
        ]);

        // Session'dan sepet verilerini al
        $cartData = session('cart_data', []);
        $cartTotal = session('cart_total', 0);

        if (empty($cartData)) {
            Log::warning('Boş sepet veya oturum zaman aşımı');
            return redirect('/')->with('error', 'Sepetiniz boş veya oturumunuz zaman aşımına uğradı.');
        }

        Log::info('Sepet bilgileri', [
            'cart_items_count' => count($cartData),
            'cart_total' => $cartTotal
        ]);

        // Ödeme işlemini gerçekleştir
        Log::info('Ödeme işlemi başlatılıyor', [
            'payment_method' => 'VakifBank'
        ]);

        $result = $this->odemeService->processPayment($cartData, $validated, $cartTotal);

        if ($result['success']) {
            Log::info('Ödeme başarılı', [
                'transaction_id' => $result['transaction_id'] ?? null,
                'amount' => $cartTotal
            ]);

            // Başarılı ödeme - sepeti temizle
            session()->forget(['cart_data', 'cart_total']);

            // Kullanıcının localStorage'daki sepetini temizle (JavaScript ile)
            return redirect('/tesekkur-sayfasi')->with('payment_success', true);
        } else {
            Log::warning('Ödeme başarısız', [
                'error_message' => $result['message'],
                'cart_total' => $cartTotal
            ]);

            // Başarısız ödeme
            return redirect()->back()->with('error', $result['message']);
        }
    }
}
