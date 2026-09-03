<?php

namespace App\Providers;

use App\Services\Api\ApiService;
use App\Services\Api\Contracts\ApiServiceInterface;
use App\Services\Import\Contracts\ImportServiceInterface;
use App\Services\Import\ImportService;
use App\Services\Property\Contracts\PropertySearchServiceInterface;
use App\Services\Property\PropertySearchService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ApiServiceInterface::class, ApiService::class);
        $this->app->bind(ImportServiceInterface::class, ImportService::class);
        $this->app->bind(PropertySearchServiceInterface::class, PropertySearchService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);
        JsonResource::withoutWrapping();

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(
            fn (): ?Password => app()->isProduction()
                ? Password::min(12)
                    ->mixedCase()
                    ->letters()
                    ->numbers()
                    ->symbols()
                    ->uncompromised()
                : null,
        );

        Model::shouldBeStrict();
        Schema::defaultStringLength(255);
    }
}
