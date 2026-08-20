<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

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
        $this->configureDefaults();
        $this->configureRateLimiting();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): Password => Password::min(app()->isProduction() ? 12 : 8)
            ->mixedCase()
            ->letters()
            ->numbers()
            ->symbols()
            ->when(app()->isProduction(), fn (Password $rule) => $rule->uncompromised())
        );
    }

    /**
     * Configure rate limiters.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api-login', fn (Request $request) => $this->apiAuthLimit($request));
        RateLimiter::for('api-signup', fn (Request $request) => $this->apiAuthLimit($request));
    }

    /**
     * Limit API auth attempts and return a JSON 429 when exceeded.
     */
    protected function apiAuthLimit(Request $request): Limit
    {
        $throttleKey = Str::transliterate(Str::lower((string) $request->input('email')).'|'.$request->ip());

        return Limit::perMinute(5)
            ->by($throttleKey)
            ->response(function (Request $request, array $headers) {
                $retryAfter = (int) ($headers['Retry-After'] ?? 60);

                return response()->json([
                    'message' => __('auth.throttle', [
                        'seconds' => $retryAfter,
                        'minutes' => (int) ceil($retryAfter / 60),
                    ]),
                ], 429, $headers);
            });
    }
}
