<?php

namespace App\Providers;

use App\Models\AuditLog;
use App\Models\ClubMember;
use App\Models\Meeting;
use App\Policies\AuditLogPolicy;
use App\Policies\ClubMemberPolicy;
use App\Policies\MeetingPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(ClubMember::class, ClubMemberPolicy::class);
        Gate::policy(Meeting::class, MeetingPolicy::class);
        Gate::policy(AuditLog::class, AuditLogPolicy::class);

        RateLimiter::for('login', function ($request) {
            return Limit::perMinute(5)->by($request->ip());
        });
    }
}
