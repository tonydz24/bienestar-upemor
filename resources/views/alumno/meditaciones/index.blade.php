@extends('layouts.dashboard')

@section('title', 'Meditación y Relajación Guiada - Bienestar UPEMOR')

@push('styles')
<style>
    .meditacion-card {
        background: #ffffff;
        border: 1px solid rgba(124, 58, 237, 0.12);
        border-radius: 1.25rem;
        transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
        display: flex;
        flex-direction: column;
        height: 100%;
    }
    .meditacion-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 28px -6px rgba(109, 40, 217, 0.14);
        border-color: rgba(124, 58, 237, 0.35);
    }
    .meditacion-icon-bubble {
        width: 52px;
        height: 52px;
        border-radius: 1rem;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }
    .cat-pill {
        border-radius: 9999px;
        padding: 0.5rem 1.15rem;
        font-weight: 600;
        font-size: 0.88rem;
        text-decoration: none;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        border: 1px solid rgba(124, 58, 237, 0.15);
        color: #475569;
        background: #ffffff;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
    }
    .cat-pill:hover {
        background: linear-gradient(135deg, #f5f3ff 0%, #ede9fe 100%);
        color: #6d28d9;
        border-color: rgba(124, 58, 237, 0.35);
        transform: translateY(-1px);
        box-shadow: 0 4px 10px rgba(109, 40, 217, 0.08);
    }
    .cat-pill:focus-visible {
        outline: 3px solid #7c3aed;
        outline-offset: 2px;
    }
    .cat-pill.active {
        background: linear-gradient(135deg, #7c3aed 0%, #6366f1 100%);
        color: #ffffff !important;
        border-color: transparent;
        box-shadow: 0 4px 14px rgba(124, 58, 237, 0.35);
        transform: translateY(-1px);
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-0 px-md-3 py-3 py-md-4">
    @if(session('status'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-4 mb-4" role="alert">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-check-circle-fill fs-5 text-success"></i>
                <div class="fw-semibold">{{ session('status') }}</div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    @endif

    <!-- Hero Card de Bienestar y Calma -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden" style="background: linear-gradient(135deg, #f5f3ff 0%, #ede9fe 50%, #e0e7ff 100%); border-left: 5px solid #7c3aed !important;">
        <div class="card-body p-4 p-lg-5">
            <div class="row align-items-center g-4">
                <div class="col-lg-8">
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 rounded-pill fw-semibold mb-3">
                        <i class="bi bi-flower1 me-1"></i> Espacio de Autocuidado y Calma
                    </span>
                    <h1 class="h2 fw-bold text-slate-800 mb-2">Meditación y Relajación Guiada</h1>
                    <p class="text-muted mb-4 lead" style="max-width: 650px;">
                        Tómate una pausa consciente. Estos ejercicios visuales interactivos están diseñados para reducir la ansiedad académica, despejar la sobrecarga mental y mejorar tu descanso.
                    </p>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('alumno.meditaciones.historial') }}" class="btn btn-outline-primary rounded-pill px-3 py-2 fw-semibold">
                            <i class="bi bi-shield-lock me-1"></i> Mi Historial Confidencial
                        </a>
                        <a href="{{ route('alumno.bitacora.index') }}" class="btn btn-light rounded-pill px-3 py-2 fw-semibold text-muted border">
                            <i class="bi bi-journal-check me-1"></i> Bitácora Emocional
                        </a>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="row g-2">
                        <div class="col-6">
                            <div class="bg-white p-3 rounded-4 shadow-sm border border-light text-center h-100">
                                <div class="text-primary fs-3 fw-bold">{{ $totalSesiones }}</div>
                                <div class="text-muted small fw-medium">Sesiones Completadas</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="bg-white p-3 rounded-4 shadow-sm border border-light text-center h-100">
                                <div class="text-success fs-3 fw-bold">{{ $minutosTotales }}'</div>
                                <div class="text-muted small fw-medium">Minutos de Calma</div>
                            </div>
                        </div>
                        <div class="col-12 mt-2">
                            <div class="bg-white p-3 rounded-4 shadow-sm border border-light d-flex align-items-center gap-3">
                                <div class="rounded-circle bg-primary-subtle text-primary p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                    <i class="bi bi-heart-fill"></i>
                                </div>
                                <div class="overflow-hidden">
                                    <div class="text-muted small text-truncate">Práctica habitual</div>
                                    <div class="fw-bold text-slate-800 text-truncate" title="{{ $meditacionFavorita?->titulo ?? 'Aún sin sesiones' }}">
                                        {{ $meditacionFavorita?->titulo ?? 'Empieza tu primera sesión' }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros de Categoría -->
    <div class="mb-4">
        <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
            <h5 class="fw-bold text-slate-800 mb-0">Explorar por Categoría</h5>
            <div class="d-flex align-items-center gap-2">
                <div class="text-muted small">
                    Mostrando <span class="fw-semibold text-dark">{{ $meditaciones->total() }}</span> ejercicios disponibles
                </div>
                @if(request()->filled('categoria') || request()->filled('q') || request()->filled('nivel'))
                    <a href="{{ route('alumno.meditaciones.index') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3 py-1">
                        <i class="bi bi-x-circle me-1"></i> Limpiar filtros
                    </a>
                @endif
            </div>
        </div>
        <div class="d-flex flex-wrap gap-2 mb-3">
            <a href="{{ route('alumno.meditaciones.index', request()->except('categoria', 'page')) }}" 
               class="cat-pill @if(!request()->filled('categoria')) active @endif"
               aria-label="Ver todas las categorías">
                <i class="bi bi-grid-fill"></i> Todas
                <span class="badge rounded-pill {{ !request()->filled('categoria') ? 'bg-white text-dark' : 'bg-light text-secondary border' }} ms-1">{{ $totalPublicadas }}</span>
            </a>
            @foreach($categorias as $key => $cat)
                @php $count = $conteoPorCategoria[$key] ?? 0; @endphp
                <a href="{{ route('alumno.meditaciones.index', array_merge(request()->except('page'), ['categoria' => $key])) }}" 
                   class="cat-pill @if(request('categoria') === $key) active @endif"
                   aria-label="Filtrar por categoría {{ $cat['label'] }}">
                    <i class="bi {{ $cat['icon'] }}"></i> {{ $cat['label'] }}
                    <span class="badge rounded-pill {{ request('categoria') === $key ? 'bg-white text-dark' : 'bg-light text-secondary border' }} ms-1">{{ $count }}</span>
                </a>
            @endforeach
        </div>
    </div>

    <!-- Barra de Búsqueda y Nivel -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3">
            <form action="{{ route('alumno.meditaciones.index') }}" method="GET" class="row g-2 align-items-center">
                @if(request()->filled('categoria'))
                    <input type="hidden" name="categoria" value="{{ request('categoria') }}">
                @endif
                <div class="col-md-7 col-lg-8">
                    <div class="input-group">
                        <span class="input-group-text bg-transparent border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" name="q" value="{{ request('q') }}" class="form-control border-start-0 ps-0" placeholder="Buscar por título, respiración, sueño, ansiedad...">
                    </div>
                </div>
                <div class="col-md-3 col-lg-3">
                    <select name="nivel" class="form-select" aria-label="Filtrar por nivel">
                        <option value="">Todos los niveles</option>
                        <option value="PRINCIPIANTE" @selected(request('nivel') === 'PRINCIPIANTE')>Principiante</option>
                        <option value="INTERMEDIO" @selected(request('nivel') === 'INTERMEDIO')>Intermedio</option>
                        <option value="AVANZADO" @selected(request('nivel') === 'AVANZADO')>Avanzado</option>
                    </select>
                </div>
                <div class="col-md-2 col-lg-1 d-flex gap-1">
                    <button type="submit" class="btn btn-primary rounded-pill w-100 fw-semibold">Filtrar</button>
                    @if(request()->filled('categoria') || request()->filled('q') || request()->filled('nivel'))
                        <a href="{{ route('alumno.meditaciones.index') }}" class="btn btn-light border rounded-pill d-inline-flex align-items-center justify-content-center px-2" title="Limpiar todos los filtros" aria-label="Limpiar todos los filtros">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Grid de Meditaciones -->
    @if($meditaciones->isEmpty())
        <div class="card border-0 shadow-sm rounded-4 p-5 text-center my-4">
            <div class="mx-auto mb-3 text-muted opacity-50" style="font-size: 3rem;">
                <i class="bi bi-flower2"></i>
            </div>
            <h5 class="fw-bold text-slate-700">No se encontraron meditaciones con esos filtros</h5>
            <p class="text-muted mb-3">Intenta cambiar la categoría o buscar con otros términos.</p>
            <div>
                <a href="{{ route('alumno.meditaciones.index') }}" class="btn btn-outline-primary rounded-pill px-4">
                    Ver todos los ejercicios
                </a>
            </div>
        </div>
    @else
        <div class="row g-4 mb-4">
            @foreach($meditaciones as $med)
                <div class="col-md-6 col-lg-4">
                    <div class="meditacion-card shadow-sm p-4">
                        <div class="d-flex align-items-start justify-content-between mb-3">
                            <div class="meditacion-icon-bubble bg-primary-subtle text-primary">
                                <i class="bi {{ $med->categoria_icon }}"></i>
                            </div>
                            <div class="d-flex flex-column align-items-end gap-1">
                                <span class="badge {{ $med->categoria_badge }} rounded-pill px-2 py-1 small">
                                    {{ $med->categoria_label }}
                                </span>
                                <span class="badge bg-light text-secondary border rounded-pill px-2 py-1 small">
                                    {{ $med->nivel_label }}
                                </span>
                            </div>
                        </div>

                        <h5 class="fw-bold text-slate-800 mb-2">{{ $med->titulo }}</h5>
                        <p class="text-muted small flex-grow-1 mb-4" style="line-height: 1.5;">
                            {{ Str::limit($med->descripcion, 130) }}
                        </p>

                        <div class="pt-3 border-top d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-3 text-muted small">
                                <span><i class="bi bi-clock me-1 text-primary"></i> {{ $med->duracion_minutos }} min</span>
                                <span><i class="bi bi-people me-1 text-secondary"></i> {{ $med->vistas_count }}</span>
                            </div>
                            <a href="{{ route('alumno.meditaciones.show', $med) }}" class="btn btn-primary rounded-pill px-3 py-1 btn-sm fw-semibold">
                                Comenzar <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="d-flex justify-content-center">
            {{ $meditaciones->links() }}
        </div>
    @endif
</div>
@endsection

