<?php

namespace App\Services;

class PaymentService
{
    public function processPayment(array $data)
    {
        // Ödeme gateway API çağrısı yapılır
        // Simüle edilmiş başarısız ödeme:
        return (object)[
            'isSuccess' => false,
            'getMessage' => fn() => 'Yetersiz bakiye'
        ];
    }
}
