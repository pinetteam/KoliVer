<?php

namespace App\Listeners;

use Statamic\Events\FormSubmitted;
use Statamic\Facades\GlobalSet;
use Illuminate\Support\Facades\Log;

class FormStatsUpdater
{
    /**
     * Form gönderildikten sonra istatistikleri güncelle
     */
    public function handle(FormSubmitted $event)
    {
        // Tüm gelen event bilgilerini logla
        Log::info('FormSubmitted Event Yakalandı', [
            'form_handle' => $event->submission->form()->handle(),
            'submission_id' => $event->submission->id(),
            'data' => $event->submission->data()
        ]);

        // Sadece "iyiligi_ulastir" formu için işlem yap
        if ($event->submission->form()->handle() !== 'iyiligi_ulastir') {
            Log::warning('Form handle uyuşmadı');
            return;
        }

        // Form verilerini al
        $data = $event->submission->data();
        $toplam_tutar = floatval($data['toplam_tutar'] ?? 0);
        $ad_soyad = $data['ad_soyad'] ?? 'İsimsiz';

        // Ödeme durumunu kontrol et
        $odeme_durumu = 'success';
        $odeme_basarili = ($odeme_durumu === 'success' || $odeme_durumu === 'Başarılı');

        Log::info('Ödeme bilgileri', [
            'toplam_tutar' => $toplam_tutar,
            'ad_soyad' => $ad_soyad,
            'odeme_durumu' => $odeme_durumu,
            'basarili_mi' => $odeme_basarili
        ]);

        // Başarısız ödeme durumunda işlemi sonlandır
        if (!$odeme_basarili) {
            Log::warning('Ödeme başarısız veya beklemede', [
                'ad_soyad' => $ad_soyad,
                'tutar' => $toplam_tutar
            ]);
            return;
        }

        try {
            // Global settings'i al
            $settings = GlobalSet::findByHandle('settings');
            if (!$settings) {
                Log::warning('Settings global set bulunamadı');
                return;
            }

            $variables = $settings->inDefaultSite()->data();

            // Mevcut değerleri al
            $current_count = intval($variables['toplam_bagis_sayisi'] ?? 0);
            $current_total = floatval(str_replace(['.', ','], ['', '.'], $variables['toplam_bagis_tutari'] ?? 0));

            Log::info('Mevcut değerler', [
                'current_count' => $current_count,
                'current_total' => $current_total
            ]);

            // Değerleri güncelle
            $new_count = $current_count + 1;
            $new_total = $current_total + $toplam_tutar;

            // Güncellenmiş değerleri kaydet
            $settings->inDefaultSite()->set('toplam_bagis_sayisi', $new_count);
            $settings->inDefaultSite()->set('toplam_bagis_tutari', number_format($new_total, 0, ',', '.'));
            $settings->save();

            Log::info('İstatistikler güncellendi', [
                'eski_bagis_sayisi' => $current_count,
                'yeni_bagis_sayisi' => $new_count,
                'eski_bagis_tutari' => $current_total,
                'yeni_bagis_tutari' => $new_total,
                'eklenen_tutar' => $toplam_tutar
            ]);
        } catch (\Exception $e) {
            Log::error('İstatistikleri güncellerken hata', [
                'hata' => $e->getMessage(),
                'satir' => $e->getLine(),
                'dosya' => $e->getFile(),
                'stack_trace' => $e->getTraceAsString()
            ]);
        }
    }
}
