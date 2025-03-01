<?php
namespace App\Listeners;

use Statamic\Events\FormSubmitted;
use App\Services\VakifBankOdemeService;
use Illuminate\Support\Facades\Log;

class CheckPaymentBeforeSaving
{
    public function handle(FormSubmitted $event)
    {
        // Hangi form için çalışacağını belirle
        if ($event->submission->form()->handle() !== 'iyiligi_ulastir') {
            return;
        }

        $formData = $event->submission->data();
        Log::info('Form gönderildi.', ['form_data' => $formData]);

        // Ödeme servisini çağır
        $odemeServisi = new VakifBankOdemeService();
        $odemeSonucu = $odemeServisi->processPayment($formData);

        Log::info('Ödeme sonucu', [
            'success' => $odemeSonucu->isSuccess(),
            'message' => $odemeSonucu->getMessage(),
        ]);

        // Ödeme başarısızsa formu kaydetmeyi iptal et
        if (!$odemeSonucu->isSuccess()) {
            Log::warning('Ödeme başarısız, form kaydedilmeyecek.', [
                'message' => $odemeSonucu->getMessage()
            ]);

            // Form kaydedilmesini engelle
            $event->submission->preventSave();
        }
    }
}
