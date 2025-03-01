<?php

namespace App\Providers;

use App\Listeners\FormStatsUpdater;
use App\Listeners\FormSubmissionListener;
use App\Listeners\PaymentProcessor;
use Illuminate\Support\ServiceProvider;

use Statamic\Events\FormSubmitted;
use Statamic\Events\FormCreating;
use Statamic\Events\FormSaving;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Statamic::vite('app', [
        //     'resources/js/cp.js',
        //     'resources/css/cp.css',
        // ]);





        // Form kaydedildikten sonra istatistikleri güncelle
        \Event::listen(FormSubmitted::class, [FormStatsUpdater::class, 'handle']);


    }
}

