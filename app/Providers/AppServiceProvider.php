<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Model;
use App\Models\Transaction;
use App\Models\Member;
use App\Models\Category;
use App\Models\CostCenter;
use App\Models\Meeting;
use App\Models\Role; // Added
use App\Models\Society; // Added
use App\Policies\TransactionPolicy;
use App\Policies\MemberPolicy;
use App\Policies\CategoryPolicy;
use App\Policies\CostCenterPolicy;
use App\Policies\MeetingPolicy;
use App\Policies\RolePolicy; // Added
use App\Policies\SocietyPolicy; // Added
use Illuminate\Support\Facades\Gate;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Model::preventLazyLoading(!app()->isProduction());
        Gate::policy(Transaction::class, TransactionPolicy::class);
        Gate::policy(Member::class, MemberPolicy::class);
        Gate::policy(Category::class, CategoryPolicy::class);
        Gate::policy(CostCenter::class, CostCenterPolicy::class);
        Gate::policy(Meeting::class, MeetingPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(Society::class, SocietyPolicy::class);
    }
}
