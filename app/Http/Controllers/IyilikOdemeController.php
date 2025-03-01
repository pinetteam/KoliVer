<?php

namespace App\Http\Controllers;

use App\Services\VakifBankOdemeService;
use Illuminate\Http\Request;
use Statamic\Facades\Form;

class IyilikOdemeController extends Controller
{
    protected $vakifbank;

    public function __construct(VakifBankOdemeService $vakifbank)
    {
        $this->vakifbank = $vakifbank;
    }

    public function handleFormSubmission(Request $request)
    {
        // Form verilerini al
        $formData = $request->all();

        \Log::info('Form verileri alındı', ['form_data' => $formData]);

        // Sepet verilerini kontrol et
        $siparisDetayi = json_decode($formData['siparis_detayi'] ?? '[]', true);
        $toplamTutar = floatval($formData['toplam_tutar'] ?? 0);

        if (empty($siparisDetayi) || $toplamTutar <= 0) {
            return redirect()->back()->with('error', 'Sepetiniz boş veya geçersiz.');
        }

        // Kart bilgilerini kontrol et
        if (empty($formData['kart_no']) ||
            empty($formData['kart_ay']) ||
            empty($formData['kart_yil']) ||
            empty($formData['kart_cvv'])) {
            return redirect()->back()->with('error', 'Kart bilgileri eksik.');
        }

        // Ödeme işlemini gerçekleştir
        $result = $this->vakifbank->processPayment($siparisDetayi, [
            'ad_soyad' => $formData['ad_soyad'],
            'telefon' => $formData['telefon'],
            'sehir' => $formData['sehir'],
            'tc_kimlik' => $formData['tc_kimlik'] ?? '',
            'dogum_tarihi' => $formData['dogum_tarihi'] ?? '',
            'kart_no' => $formData['kart_no'],
            'kart_ay' => $formData['kart_ay'],
            'kart_yil' => $formData['kart_yil'],
            'kart_cvv' => $formData['kart_cvv'],
        ], $toplamTutar);

        // Ödeme başarılı değilse hata mesajıyla geri dön
        if (!$result['success']) {
            \Log::error('Ödeme başarısız', ['error' => $result['message']]);
            return redirect()->back()->with('error', 'Ödeme işlemi başarısız: ' . $result['message']);
        }

        // Ödeme başarılıysa formu kaydet
        // Kart bilgilerini çıkar
        unset($formData['kart_no'], $formData['kart_ay'], $formData['kart_yil'], $formData['kart_cvv']);

        // Ödeme bilgilerini ekle
        $formData['odeme_durumu'] = 'Başarılı';
        $formData['odeme_mesaji'] = $result['message'] ?? '';
        $formData['odeme_referansi'] = $result['transaction_id'] ?? '';

        // Statamic Form submission oluştur
        $form = Form::find('iyiligi_ulastir');
        $submission = $form->createSubmission();
        $submission->data($formData);
        $submission->save();

        \Log::info('Form başarıyla kaydedildi', ['submission_id' => $submission->id()]);

        // Başarı mesajıyla teşekkür sayfasına yönlendir
        return redirect('/tesekkur-sayfasi')->with('payment_success', true);
    }
}
