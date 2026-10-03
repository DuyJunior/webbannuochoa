<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Perfume;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

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
        // Read after configuration is loaded, including when config:cache is enabled.
        TrustProxies::at(config('deployment.trusted_proxies', []));
        Paginator::useBootstrapFour();

        // Keep Laravel's notification and signed URL; customize only the email presentation.
        VerifyEmail::toMailUsing(function ($notifiable, string $url): MailMessage {
            return (new MailMessage)
                ->subject('Soopi · Xác thực email của bạn')
                ->action('Xác thực email', $url)
                ->view(['html' => 'emails.auth.verify-email', 'text' => 'emails.auth.verify-email-text'], [
                    'customerName' => $notifiable->name,
                    'verificationUrl' => $url,
                    'expiresMinutes' => (int) config('auth.verification.expire', 60),
                ]);
        });

        View::composer(['layouts.store', 'home'], function ($view) {
            try {
                if (! isset($view->categories) && Schema::hasTable('categories') && Schema::hasTable('perfumes')) {
                    $categories = Category::query()
                        ->withCount(['perfumes' => fn ($query) => $query->where('is_active', true)])
                        ->orderBy('id')
                        ->take(6)
                        ->get();
                    $view->with('categories', $categories);
                }
                if (! isset($view->genderCounts) && Schema::hasTable('perfumes')) {
                    $genderCounts = Perfume::query()
                        ->where('is_active', true)
                        ->selectRaw('gender, count(*) as total')
                        ->groupBy('gender')
                        ->pluck('total', 'gender');
                    $view->with('genderCounts', $genderCounts);
                    $view->with('totalPerfumes', $genderCounts->sum());
                }
            } catch (\Throwable $e) {
                // Ignore during migrations or initial setup
            }
        });
    }
}
