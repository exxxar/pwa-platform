<?php

namespace App\Providers;

use App\Facades\PromoCodeService;
use App\Services\CashBackService;
use App\Services\FrontPadService;
use App\Services\GEOService;
use App\Services\IIKOService;
use App\Services\MessageService;
use App\Services\PartnerService;
use App\Services\PaymentService;
use App\Services\ProductService;
use App\Services\StoryService;
use App\Services\TableService;
use App\Services\TenantService;
use App\Services\TenantUserService;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(TenantService::class, function () {
            return new TenantService();
        });

        $this->app->singleton(MessageService::class, function () {
            return new MessageService();
        });

        $this->app->singleton(CashBackService::class, function () {
            return new CashBackService();
        });

        $this->app->singleton(ProductService::class, function () {
            return new ProductService();
        });

        $this->app->singleton(GEOService::class, function () {
            return new GEOService();
        });

        $this->app->singleton(PartnerService::class, function () {
            return new PartnerService();
        });

        $this->app->singleton(FrontPadService::class, function () {
            return new FrontPadService();
        });

        $this->app->singleton(IIKOService::class, function () {
            return new IIKOService();
        });

        $this->app->singleton(PromoCodeService::class, function () {
            return new PromoCodeService();
        });

        $this->app->singleton(PaymentService::class, function () {
            return new PaymentService();
        });

        $this->app->singleton(TenantUserService::class, function () {
            return new TenantUserService();
        });

        $this->app->singleton(StoryService::class, function () {
            return new StoryService();
        });

        $this->app->singleton(TableService::class, function () {
            return new TableService();
        });

    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {



        Blade::directive('permission', function ($permission) {
            return "<?php if(auth()->check() && auth()->user()->hasPermission($permission)): ?>";
        });

        Blade::directive('endpermission', function () {
            return "<?php endif; ?>";
        });

        Blade::directive('role', function ($role) {
            return "<?php if(auth()->check() && auth()->user()->hasRole($role)): ?>";
        });

        Blade::directive('endrole', function () {
            return "<?php endif; ?>";
        });

        Blade::directive('anyrole', function ($roles) {
            return "<?php if(auth()->check() && auth()->user()->roles()->whereIn('name', $roles)->exists()): ?>";
        });

        Blade::directive('endanyrole', function () {
            return "<?php endif; ?>";
        });

        Blade::directive('anypermission', function ($permissions) {
            return "<?php if(auth()->check() && auth()->user()->roles()->whereHas('permissions', function(\$q) use (\$permissions) { \$q->whereIn('name', \$permissions); })->exists()): ?>";
        });

        Blade::directive('endanypermission', function () {
            return "<?php endif; ?>";
        });

    }
}
