<?php

namespace App\Providers;

use App\Facades\PromoCodeService;
use App\Models\Tenant\TenantDialog;
use App\Models\Tenant\TenantUser;
use App\Observers\TenantUserObserver;
use App\Services\Tenants\CashBackService;
use App\Services\Tenants\FrontPadService;
use App\Services\Tenants\GEOService;
use App\Services\Tenants\IIKOService;
use App\Services\Tenants\MessageService;
use App\Services\Tenants\PartnerService;
use App\Services\Tenants\PaymentService;
use App\Services\Tenants\ProductService;
use App\Services\Tenants\StoryService;
use App\Services\Tenants\TableService;
use App\Services\Tenants\TenantService;
use App\Services\Tenants\TenantUserService;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
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
        TenantUser::observe(TenantUserObserver::class);

        URL::forceScheme('https');

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

        Route::bind('dialog', function ($value) {
            $tenant = app('tenant');
            $user = auth('tenant')->user();

            // Поддержка как ID, так и external_task_id
            $dialog = TenantDialog::where('tenant_id', $tenant->id)
                ->where(function ($q) use ($value) {
                    $q->where('id', $value)
                        ->orWhere('external_task_id', $value);
                });

            // Если есть пользователь — проверяем принадлежность
            if ($user) {
                $dialog->where('tenant_user_id', $user->id);
            }

            return $dialog->firstOrFail();
        });

    }
}
