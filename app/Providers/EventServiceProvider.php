<?php

namespace App\Providers;

use App\Services\VakifBankOdemeService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Statamic\Facades\Form;
use App\Listeners\CheckPaymentBeforeSaving;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        FormSubmitted::class => [
            CheckPaymentBeforeSaving::class,
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        Form::extend('iyiligi_ulastir', function (Submission $submission) {
            $odemeServisi = new VakifBankOdemeService();
            $odemeSonucu = $odemeServisi->processPayment($submission->data());

            // Log kaydı ekleyelim
            Log::info('Form işleniyor.', [
                'form_data' => $submission->data(),
                'payment_success' => $odemeSonucu->isSuccess(),
                'payment_message' => $odemeSonucu->getMessage()
            ]);

            if (!$odemeSonucu->isSuccess()) {
                Log::warning('Ödeme başarısız, form kaydedilmeyecek.', [
                    'message' => $odemeSonucu->getMessage()
                ]);
                return false;
            }

            return $submission;
        });
    }
}
