<?php

namespace App\Providers;

use App\Models\Role;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

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
        $this->configureDefaults();
        $this->configureMorphMap();
        $this->configureAuthorization();
        $this->registerBlueprintMacros();
    }

    /**
     * Stable names in `auditable_type` / `model_type` instead of class names.
     * Every model that is audited or gets roles must be listed here.
     */
    protected function configureMorphMap(): void
    {
        Relation::enforceMorphMap([
            'user' => User::class,
            'role' => Role::class,
        ]);
    }

    /** Super admins pass every permission check; everyone else goes through roles/permissions. */
    protected function configureAuthorization(): void
    {
        Gate::before(fn (User $user) => $user->isSuperAdmin() ? true : null);
    }

    /** `$table->userStamps()` adds the columns that the UserStamp trait fills. */
    protected function registerBlueprintMacros(): void
    {
        Blueprint::macro('userStamps', function (): void {
            /** @var Blueprint $this */
            $this->unsignedBigInteger('created_by')->nullable()->index();
            $this->unsignedBigInteger('updated_by')->nullable()->index();
            $this->unsignedBigInteger('deleted_by')->nullable()->index();
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
