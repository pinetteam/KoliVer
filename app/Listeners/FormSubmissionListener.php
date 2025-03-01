<?php

namespace App\Listeners;

use Statamic\Events\FormSubmitted;
use Statamic\Events\FormSaving;
use Statamic\Facades\GlobalSet;
use Illuminate\Support\Facades\Log;

class FormSubmissionListener
{
    /**
     * Form kaydedilmeden önce ödeme işlemi yap
     */
    public function handle(FormSaving  $event)
    {
        // Sadece "iyiligi_ulastir" formu için işlem yap
        if ($event->submission->form()->handle() !== 'iyiligi_ulastir') {
            return;
        }

        // Form verileri
        $data = $event->submission->data();
        $toplam_tutar = floatval($data['toplam_tutar'] ?? 0);
        $kart_no = $data['kart_no'] ?? '';
        $ad_soyad = $data['ad_soyad'] ?? '';

        Log::info('Ödeme işlemi başlatıldı: ' . $ad_soyad . ' - ' . $toplam_tutar . '₺');

        // Test için basit bir ödeme simülasyonu
        $success = true; // Gerçek uygulamada API yanıtını kontrol edin
        $message = 'Ödeme başarıyla tamamlandı';
        $referenceId = 'TEST-' . time();

        // Sonucu loga yaz
        Log::info('Ödeme sonucu: ' . ($success ? 'Başarılı' : 'Başarısız') . ' - Ref: ' . $referenceId);

        // Ödeme sonucu verilerini form verilerine ekle
        $event->submission->set('odeme_durumu', $success ? 'Başarılı' : 'Başarısız');
        $event->submission->set('odeme_referans', $referenceId);
        $event->submission->set('odeme_mesaji', $message);

        // Hassas kart verilerini kaldır veya maskele
        $maskedCardNumber = substr($kart_no, 0, 4) . ' **** **** ' . substr($kart_no, -4);
        $event->submission->set('kart_no', $maskedCardNumber);
        $event->submission->remove('kart_cvv'); // CVV verisi tutulmamalı

        // Başarısız ödeme durumunda formu kaydetme
        if (!$success) {
            $event->submission->addError('odeme', $message);
            return false; // Formu kaydetmeyi durdurur
        }

        return true; // Form kaydına devam et
    }

    /**
     * Form kaydedildikten sonra istatistikleri güncelle
     */
    public function handleSubmission(FormSubmitted $event)
    {
        // Sadece "iyiligi_ulastir" formu için işlem yap
        if ($event->submission->form()->handle() !== 'iyiligi_ulastir') {
            return;
        }

        // Form verileri
        $data = $event->submission->data();
        $toplam_tutar = floatval($data['toplam_tutar'] ?? 0);

        // Global settings'i al
        $settings = GlobalSet::findByHandle('settings');
        if (!$settings) {
            Log::warning('Settings global set bulunamadı');
            return; // Settings bulunamadı
        }

        $variables = $settings->inDefaultSite()->data();

        // Mevcut değerleri al
        $current_count = intval($variables['toplam_bagis_sayisi'] ?? 0);
        $current_total = floatval(str_replace(['.', ','], ['', '.'], $variables['toplam_bagis_tutari'] ?? 0));

        // Değerleri güncelle
        $new_count = $current_count + 1;
        $new_total = $current_total + $toplam_tutar;

        // Güncellenmiş değerleri kaydet
        $settings->inDefaultSite()->set('toplam_bagis_sayisi', $new_count);
        $settings->inDefaultSite()->set('toplam_bagis_tutari', number_format($new_total, 0, ',', '.'));
        $settings->save();

        Log::info('İstatistikler güncellendi: ' . $new_count . ' bağış, ' . $new_total . '₺');
    }
}
