<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class VakifBankOdemeService
{
    public function processPayment(array $data)
    {
        // Banka API entegrasyonu burada olacak
        // Simüle edilmiş response:
        $response = new class {
            public function isSuccess() { return false; }
            public function getMessage() { return 'Kart limiti yetersiz'; }
        };

        // Log kaydı ekleyelim
        Log::info('Ödeme işlemi yapıldı.', [
            'success' => $response->isSuccess(),
            'message' => $response->getMessage(),
            'data' => $data
        ]);

        return $response;
    }
}
