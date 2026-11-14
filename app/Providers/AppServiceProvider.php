<?php

namespace App\Providers;

use App\Models\CitaOrientacion;
use App\Models\DocenteAsignacion;
use App\Models\EvaluacionIntento;
use App\Models\InstrumentoEvaluacion;
use App\Models\RecompensaActivada;
use App\Models\RecompensaCanje;
use App\Policies\AcademicoPolicy;
use App\Policies\CitaPolicy;
use App\Policies\EvaluacionPolicy;
use App\Policies\RecompensaPolicy;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
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

        Gate::policy(EvaluacionIntento::class, EvaluacionPolicy::class);
        Gate::policy(InstrumentoEvaluacion::class, EvaluacionPolicy::class);
        Gate::policy(CitaOrientacion::class, CitaPolicy::class);
        Gate::policy(DocenteAsignacion::class, AcademicoPolicy::class);
        Gate::policy(RecompensaActivada::class, RecompensaPolicy::class);
        Gate::policy(RecompensaCanje::class, RecompensaPolicy::class);
    }
}
