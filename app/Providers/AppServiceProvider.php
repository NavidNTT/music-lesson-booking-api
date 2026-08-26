<?php

namespace App\Providers;

use App\Domain\Wallet\Services\PaymentGatewayInterface;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
            // Local gateway for dev/staging. Swap for Stripe/PayPal in production.
    $this->app->bind(
        \App\Domain\Wallet\Services\PaymentGatewayInterface::class,
        \App\Domain\Wallet\Services\LocalWalletPaymentGateway::class
    );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        
        // The API's verification route is named api.v1.verification.verify,
        // so the default notification (which expects "verification.verify")
        // needs a custom URL builder.
        VerifyEmail::createUrlUsing(function (object $notifiable) {
            return URL::temporarySignedRoute(
                'api.v1.verification.verify',
                now()->addMinutes(60),
                [
                    'id' => $notifiable->getKey(),
                    'hash' => sha1($notifiable->getEmailForVerification()),
                ]
            );
        });

        // Password reset links must land on the frontend app, not a
        // (non-existent) backend web route.
        ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
            $frontendUrl = rtrim(config('app.frontend_url', config('app.url')), '/');

            return $frontendUrl.'/reset-password?token='.$token.'&email='.urlencode($notifiable->getEmailForPasswordReset());
        });
    }
}
