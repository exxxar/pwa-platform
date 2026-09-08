<?php
// app/Providers/AgentServiceProvider.php
namespace App\Providers;

use App\Services\Agent\{
    AgentService,
    AgentClientService,
    AgentTenantService,
    AgentFinanceService,
    InvoiceService,
    AgentDocumentService,
    AgentReferralService,
    MarketingService
};
use Illuminate\Support\ServiceProvider;

class AgentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AgentService::class);
        $this->app->singleton(AgentClientService::class);
        $this->app->singleton(AgentTenantService::class);
        $this->app->singleton(AgentFinanceService::class);
        $this->app->singleton(InvoiceService::class);
        $this->app->singleton(AgentDocumentService::class);
        $this->app->singleton(AgentReferralService::class);
        $this->app->singleton(MarketingService::class);
    }

    public function boot(): void
    {
        //
    }
}
