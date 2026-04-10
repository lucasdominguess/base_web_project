<?php

namespace App\Providers;

use App\Enums\Roles;

use App\Services\LdapService;
use App\Services\DomPdfService;
use App\Services\XssCleanService;
use App\Interfaces\LdapInterface;
use App\Services\GoogleAuthService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use App\Interfaces\SanitizerInterface;
use App\Interfaces\SocialAuthInterface;
use Illuminate\Support\ServiceProvider;
use App\Interfaces\PdfExporterInterface;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(SanitizerInterface::class, XssCleanService::class);
        $this->app->bind(LdapInterface::class, LdapService::class);
        $this->app->bind(PdfExporterInterface::class, DomPdfService::class);
        // $this->app->bind(SocialAuthInterface::class, GoogleAuthService::class);
        // $this->app->register(L5SwaggerServiceProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Route::pattern('id', '[0-9]+');
        // Gate para verificar se o usuário é um Administrador
        Gate::define('Gate-Admin', function ($user) {
            // return $user->hasRole(Roles::ADMIN->label());
        });
    }
}
