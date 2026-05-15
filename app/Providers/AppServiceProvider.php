<?php

namespace App\Providers;

use App\Models\ApprovalPlan;
use App\Models\OverdueExtension;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
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
        Paginator::useBootstrapFive();

        View::composer(['templates.partials.sidebar', 'templates.partials.menu.accounting', 'templates.partials.menu.admin'], function ($view): void {
            $pendingExtensionsCount = 0;
            if (Auth::check() && Auth::user()?->can('approve_overdue_extension')) {
                $pendingExtensionsCount = OverdueExtension::query()->pending()->count();
            }

            $view->with('pendingExtensionsCount', $pendingExtensionsCount);
        });

        View::composer('templates.partials.topbar', function ($view): void {
            $unreadRequestorReplyCount = 0;
            if (Auth::check() && Auth::user()?->can('akses_approval_request')) {
                $unreadRequestorReplyCount = ApprovalPlan::unreadRequestorReplyCountForApprover((int) Auth::id());
            }
            $view->with('unreadRequestorReplyCount', $unreadRequestorReplyCount);
        });
    }
}
