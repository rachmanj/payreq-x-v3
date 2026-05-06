<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        \App\Models\Pcbc::class => \App\Policies\PcbcPolicy::class,
        \App\Models\Anggaran::class => \App\Policies\AnggaranPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        Gate::before(function ($user, string $ability) {
            if ($ability !== 'approve_overdue_extension') {
                return null;
            }

            if ($user !== null && method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['superadmin', 'admin'])) {
                return true;
            }

            return null;
        });
    }
}
