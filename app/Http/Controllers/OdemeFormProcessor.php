<?php

namespace App\Statamic\Forms;

use Statamic\Forms\Submission;
use App\Services\VakifBankOdemeService;
use Illuminate\Support\Facades\Log;

class OdemeFormProcessor
{
    protected $odemeService;

    public function __construct(VakifBankOdemeService $odemeService)
    {
        $this->odemeService = $odemeService;
    }

    public function handle(Submission $submission)
    {
        Log::info('Form submission işleme alındı', [
            'submission_data' => $submission->data()
        ]);

        // Submission verilerinden ödeme bilgilerini çıkar
        $paymentData = $this->preparePaymentData($submission);

        // Ödeme işlemini gerçekleştir
        $result = $this->odemeService->processPayment($paymentData);

        if ($result['success']) {
            Log::info('Ödeme başarılı', [
                'transaction_id' => $result['transaction_id'] ?? null
            ]);
            return true;
        } else {
            Log::warning('Ödeme başarısız', [
                'error_message' => $result['message']
            ]);
            return false;
        }
    }

    private function preparePaymentData(Submission $submission)
    {
        // Submission verilerinden ödeme için gerekli bilgileri hazırla
        return [
            'cart_data' => $submission->get('cart_data', []),
            'customer_data' => [
                'ad_soyad' => $submission->get('ad_soyad'),
                'telefon' => $submission->get('telefon'),
                // Diğer gerekli alanlar
            ],
            'cart_total' => $submission->get('cart_total', 0)
        ];
    }
}
