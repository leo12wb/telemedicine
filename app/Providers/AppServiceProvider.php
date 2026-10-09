<?php

namespace App\Providers;

use App\Models\Specialty;
use App\Models\User;
use App\Policies\SpecialtyPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider;

class AppServiceProvider extends AuthServiceProvider
{
    protected $policies = [
        User::class      => UserPolicy::class,
        Specialty::class => SpecialtyPolicy::class,
    ];

    public function register(): void {}

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
