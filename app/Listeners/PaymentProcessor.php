<?php

namespace App\Listeners;

use Statamic\Events\FormCreating;
use Illuminate\Support\Facades\Log;
use Statamic\Facades\Form;
use Statamic\Exceptions\ValidationException;

class PaymentProcessor
{
    public function handle(FormCreating $event)
    {
        Log::info('PaymentProcessor tetiklendi', [
            'form_handle' => $event->submission->form()->handle()
        ]);

        // Sadece "iyiligi_ulastir" formu için işlem yap
        if ($event->submission->form()->handle() !== 'iyiligi_ulastir') {
            Log::warning('Form handle uyuşmadı');
            return;
        }

        $data = $event->submission->data();
        $toplam_tutar = floatval($data['toplam_tutar'] ?? 0);
        $kart_no = preg_replace('/\s+/', '', $data['kart_no'] ?? '');
        $ad_soyad = $data['ad_soyad'] ?? 'İsimsiz';

        Log::info('Ödeme işlemi kontrolü başladı', [
            'ad_soyad' => $ad_soyad,
            'tutar' => $toplam_tutar,
            'kart_son4' => substr($kart_no, -4)
        ]);

        // Ödeme simülasyonu: Son 4 hanesi 1234 olan kartlar başarılı, diğerleri başarısız
        $odeme_basarili = substr($kart_no, -4) === '1234';

        if (!$odeme_basarili) {
            Log::warning('Ödeme başarısız: Yetersiz bakiye', [
                'ad_soyad' => $ad_soyad,
                'tutar' => $toplam_tutar,
                'kart_son4' => substr($kart_no, -4)
            ]);

            // Doğrudan validasyon hatası fırlat
            throw new ValidationException([
                'odeme' => 'Ödeme işlemi reddedildi: Yetersiz bakiye veya geçersiz kart.'
            ]);
        }

        // Ödeme başarılı
        Log::info('Ödeme başarılı - form kaydına devam ediliyor', [
            'ad_soyad' => $ad_soyad,
            'tutar' => $toplam_tutar,
            'ref' => 'REF-' . time()
        ]);

        return $event->submission;
    }
}
