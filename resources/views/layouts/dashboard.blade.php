<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#181c2e">
    <title>@yield('title', 'Bienestar UPEMOR')</title>
    <!-- Bootstrap Icons (Local institucional con fallback a CDN) -->
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" onerror="this.onerror=null;this.href='{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}';">
    @vite(['resources/css/bienestar-bootstrap.css', 'resources/js/bootstrap-app.js'])
    <style>
        .notif-dropdown-item.is-unread,
        .notif-dropdown-item.es-no-leida {
            background-color: #f3f0ff !important;
            border-left: 4px solid #6366f1 !important;
            border-bottom: 1px solid #e0e7ff !important;
        }
        .notif-dropdown-item.is-unread:hover,
        .notif-dropdown-item.es-no-leida:hover {
            background-color: #ede9fe !important;
        }
        .notif-dropdown-item.is-read,
        .notif-dropdown-item.es-leida {
            background-color: #ffffff !important;
            border-left: 4px solid transparent !important;
            border-bottom: 1px solid #f1f5f9 !important;
        }
        .notif-unread-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: #4f46e5;
            display: inline-block;
            flex-shrink: 0;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.2);
        }
        .notificacion-item.es-no-leida,
        .notificacion-item.is-unread {
            background-color: #f3f0ff !important;
            border-left: 4px solid #6366f1 !important;
        }
        .notificacion-item.es-leida,
        .notificacion-item.is-read {
            background-color: #ffffff !important;
            border-left: 4px solid transparent !important;
        }
        /* Regla global de modales: protección contra herencia de text-nowrap y desbordamiento en viewport estrecho o zoom (Módulo B) */
        .modal,
        .modal-dialog,
        .modal-content,
        .modal-header,
        .modal-body,
        .modal-footer {
            white-space: normal !important;
            overflow-wrap: break-word !important;
            word-break: break-word !important;
            max-width: 100% !important;
            box-sizing: border-box !important;
        }
        .modal-body p,
        .modal-body div,
        .modal-body span,
        .modal-body label,
        .modal-title {
            white-space: normal !important;
            overflow-wrap: break-word !important;
            word-break: break-word !important;
        }
        @media (max-width: 576px) {
            .modal-dialog {
                margin: 0.5rem !important;
                max-width: calc(100% - 1rem) !important;
            }
        }
        @media (prefers-reduced-motion: reduce) {
            #liveNotificationToast,
            .notif-dropdown-item,
            .notificacion-item,
            #toastProgressBar {
                transition: none !important;
                animation: none !important;
            }
        }
    </style>
    @stack('styles')
</head>
<body class="bienestar-portal">
    @php
        $user = Auth::user();
        $roleName = $user->rol?->nombre ?? 'Usuario';
        $isAdmin = ($roleName === 'Administrador');
        $isOrientador = ($roleName === 'Orientador') || $user->esOrientador();
        $isDocente = (($roleName === 'Docente') || $user->hasRole('Docente')) && !$isOrientador;
        $isAlumno = ($roleName === 'Alumno');
        $nombreCompleto = trim($user->nombre . ' ' . $user->apellido_paterno);
        $totalNoLeidas = $user ? $user->unreadNotifications()->count() : 0;
        $notificacionesRecientes = $user ? $user->notifications()->orderByDesc('created_at')->orderByDesc('id')->take(5)->get() : collect();
        $notificacionesNoLeidasRecientes = $user ? $user->unreadNotifications()->orderByDesc('created_at')->orderByDesc('id')->take(10)->get()->map(function ($notif) {
            $data = is_array($notif->data) ? $notif->data : [];
            $tipo = (string) ($data['tipo'] ?? 'general');
            $titulo = trim(strip_tags((string) ($data['titulo'] ?? 'Nueva notificación')));
            $mensaje = trim(strip_tags((string) ($data['mensaje'] ?? '')));
            $toastMensaje = $mensaje;
            if (in_array($tipo, ['alerta_riesgo', 'clinico', 'expediente_clinico'], true)
                || preg_match('/(puntaje\s*cl[ií]nico|diagn[oó]stico|ansiedad\s*severa|depresi[oó]n\s*severa)/iu', $toastMensaje)) {
                $toastMensaje = 'Tienes un aviso confidencial en tu bandeja institucional. Haz clic para consultarlo de forma segura.';
            }
            return [
                'id' => (string) $notif->id,
                'titulo' => $titulo !== '' ? $titulo : 'Nueva notificación',
                'mensaje' => $mensaje,
                'toast_mensaje' => $toastMensaje,
                'tipo' => $tipo,
                'is_unread' => true,
                'url' => route('notificaciones.ir', $notif->id),
                'leer_url' => route('notificaciones.leer', $notif->id),
                'created_at' => $notif->created_at?->diffForHumans() ?? 'Ahora',
            ];
        })->values()->all() : [];
    @endphp

    <div class="portal-wrapper">
        <!-- Sidebar lateral estilo azul oscuro institucional -->
        <aside class="portal-sidebar" id="portalSidebar" aria-label="Menú principal de navegación">
            <div class="portal-brand-box">
                <a class="portal-brand" href="{{ $isAdmin ? route('admin.dashboard') : ($isDocente ? route('docente.dashboard') : ($isOrientador ? route('docente.citas.index') : route('alumno.dashboard'))) }}">
                    <!-- Logo corazón con cerebro institucional -->
                    <span class="portal-logo-icon" aria-hidden="true">
                        <svg viewBox="0 0 40 40" width="36" height="36" fill="none">
                            <defs>
                                <linearGradient id="brandGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                                    <stop offset="0%" stop-color="#ff4f81" />
                                    <stop offset="50%" stop-color="#8a3ffc" />
                                    <stop offset="100%" stop-color="#33b1ff" />
                                </linearGradient>
                            </defs>
                            <path d="M20 34C20 34 6 25 6 15C6 9.5 10 5.5 15.5 5.5C18 5.5 19.5 6.8 20 7.8C20.5 6.8 22 5.5 24.5 5.5C30 5.5 34 9.5 34 15C34 25 20 34 20 34Z"
                                  stroke="url(#brandGrad)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
                            <path d="M16 11C13.5 12.5 13 16 14 18.5M20 9V29M24 11C26.5 12.5 27 16 26 18.5M14 22C15 24 18 25 20 25M26 22C25 24 22 25 20 25"
                                  stroke="url(#brandGrad)" stroke-width="1.8" stroke-linecap="round" />
                        </svg>
                    </span>
                    <span class="portal-brand-text">
                        <span class="brand-sub">Bienestar</span>
                        <strong class="brand-title">UPEMOR</strong>
                    </span>
                </a>
            </div>

            <nav class="portal-nav" aria-label="Navegación del portal">
                <ul class="portal-nav-list">
                    @if ($isAlumno)
                        <!-- Menú exclusivo para Alumnos -->
                        <li>
                            <a href="{{ route('alumno.dashboard') }}" class="portal-nav-link @if(request()->routeIs('alumno.dashboard')) active @endif">
                                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M3 9.5L12 3l9 6.5V20a1 1 0 01-1 1h-5v-6H9v6H4a1 1 0 01-1-1V9.5z"/>
                                </svg>
                                <span>Inicio</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('alumno.evaluaciones') }}" class="portal-nav-link @if(request()->routeIs('alumno.evaluaciones*') || request()->routeIs('alumno.tests*')) active @endif">
                                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                                </svg>
                                <span>Tests</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('alumno.citas.index') }}" class="portal-nav-link @if(request()->routeIs('alumno.citas.*')) active @endif">
                                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                    <line x1="16" y1="2" x2="16" y2="6"/>
                                    <line x1="8" y1="2" x2="8" y2="6"/>
                                    <line x1="3" y1="10" x2="21" y2="10"/>
                                </svg>
                                <span>Citas</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('alumno.meditaciones.index') }}" class="portal-nav-link @if(request()->routeIs('alumno.meditaciones.*')) active @endif">
                                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M12 2a10 10 0 100 20 10 10 0 000-20z"/>
                                    <path d="M12 6a6 6 0 016 6c0 3-3 6-6 6s-6-3-6-6a6 6 0 016-6z"/>
                                </svg>
                                <span>Meditación y Calma</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('alumno.bitacora.index') }}" class="portal-nav-link @if(request()->routeIs('alumno.bitacora.*')) active @endif">
                                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                                    <polyline points="14 2 14 8 20 8"/>
                                    <line x1="16" y1="13" x2="8" y2="13"/>
                                    <line x1="16" y1="17" x2="8" y2="17"/>
                                    <polyline points="10 9 9 9 8 9"/>
                                </svg>
                                <span>Bitácora</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('alumno.academico.index') }}" class="portal-nav-link @if(request()->routeIs('alumno.academico.*')) active @endif">
                                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
                                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
                                </svg>
                                <span>Mis Asignaturas</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('alumno.recompensas.index') }}" class="portal-nav-link @if(request()->routeIs('alumno.recompensas.*')) active @endif">
                                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                                </svg>
                                <span>Recompensas</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('alumno.encuestas.index') }}" class="portal-nav-link @if(request()->routeIs('alumno.encuestas.*')) active @endif">
                                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M9 11l3 3L22 4"/>
                                    <path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/>
                                </svg>
                                <span>Encuestas</span>
                            </a>
                        </li>

                    @elseif ($isAdmin)
                        <!-- Menú para Administradores -->
                        <li>
                            <a href="{{ route('admin.dashboard') }}" class="portal-nav-link @if(request()->routeIs('admin.dashboard')) active @endif">
                                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M3 9.5L12 3l9 6.5V20a1 1 0 01-1 1h-5v-6H9v6H4a1 1 0 01-1-1V9.5z"/>
                                </svg>
                                <span>Inicio</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.solicitudes.index') }}" class="portal-nav-link @if(request()->routeIs('admin.solicitudes.*')) active @endif">
                                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                                    <polyline points="14 2 14 8 20 8"/>
                                    <line x1="16" y1="13" x2="8" y2="13"/>
                                    <line x1="16" y1="17" x2="8" y2="17"/>
                                </svg>
                                <span>Solicitudes</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.carreras.index') }}" class="portal-nav-link @if(request()->routeIs('admin.carreras.*') || request()->routeIs('admin.planes.*')) active @endif">
                                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M4 19.5A2.5 2.5 0 016.5 17H20"/>
                                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"/>
                                </svg>
                                <span>Programas y Planes</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.periodos.index') }}" class="portal-nav-link @if(request()->routeIs('admin.periodos.*')) active @endif">
                                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                    <line x1="16" y1="2" x2="16" y2="6"/>
                                    <line x1="8" y1="2" x2="8" y2="6"/>
                                    <line x1="3" y1="10" x2="21" y2="10"/>
                                </svg>
                                <span>Periodos Académicos</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.grupos.index') }}" class="portal-nav-link @if(request()->routeIs('admin.grupos.*')) active @endif">
                                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/>
                                    <circle cx="9" cy="7" r="4"/>
                                    <path d="M23 21v-2a4 4 0 00-3-3.87"/>
                                    <path d="M16 3.13a4 4 0 010 7.75"/>
                                </svg>
                                <span>Grupos Académicos</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.docente-asignaciones.index') }}" class="portal-nav-link @if(request()->routeIs('admin.docente-asignaciones.*')) active @endif">
                                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M16 4h2a2 2 0 012 2v14a2 2 0 01-2 2H6a2 2 0 01-2-2V6a2 2 0 012-2h2"/>
                                    <rect x="8" y="2" width="8" height="4" rx="1" ry="1"/>
                                </svg>
                                <span>Asignaciones Docentes</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.recompensas.catalogo') }}" class="portal-nav-link @if(request()->routeIs('admin.recompensas.*')) active @endif">
                                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                                </svg>
                                <span>Catálogo Recompensas</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.usuarios.index') }}" class="portal-nav-link @if(request()->routeIs('admin.usuarios.*')) active @endif">
                                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/>
                                    <circle cx="9" cy="7" r="4"/>
                                    <path d="M23 21v-2a4 4 0 00-3-3.87"/>
                                    <path d="M16 3.13a4 4 0 010 7.75"/>
                                </svg>
                                <span>Usuarios</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.meditaciones.index') }}" class="portal-nav-link @if(request()->routeIs('admin.meditaciones.*')) active @endif">
                                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"/>
                                    <path d="M12 6a6 6 0 016 6c0 3-3 6-6 6s-6-3-6-6a6 6 0 016-6z"/>
                                </svg>
                                <span>Meditaciones</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.encuestas.index') }}" class="portal-nav-link @if(request()->routeIs('admin.encuestas.*')) active @endif">
                                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M9 11l3 3L22 4"/>
                                    <path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/>
                                </svg>
                                <span>Encuestas</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.tests.index') }}" class="portal-nav-link @if(request()->routeIs('admin.tests.*')) active @endif">
                                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                                </svg>
                                <span>Gestión de Tests</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.reportes.index') }}" class="portal-nav-link @if(request()->routeIs('admin.reportes.*')) active @endif">
                                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M18 20V10"/>
                                    <path d="M12 20V4"/>
                                    <path d="M6 20v-6"/>
                                </svg>
                                <span>Reportes</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.respaldos.index') }}" class="portal-nav-link @if(request()->routeIs('admin.respaldos.*')) active @endif">
                                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <ellipse cx="12" cy="5" rx="9" ry="3"/>
                                    <path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/>
                                    <path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/>
                                </svg>
                                <span>Respaldos DB</span>
                            </a>
                        </li>

                    @elseif ($isDocente)
                        <!-- Menú para Docentes / Asesores -->
                        <li>
                            <a href="{{ route('docente.dashboard') }}" class="portal-nav-link @if(request()->routeIs('docente.dashboard')) active @endif">
                                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M3 9.5L12 3l9 6.5V20a1 1 0 01-1 1h-5v-6H9v6H4a1 1 0 01-1-1V9.5z"/>
                                </svg>
                                <span>Inicio</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('docente.academico.index') }}" class="portal-nav-link @if(request()->routeIs('docente.academico.*')) active @endif">
                                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M4 19.5A2.5 2.5 0 016.5 17H20"/>
                                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"/>
                                </svg>
                                <span>Mis Asignaturas</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('docente.recompensas.index') }}" class="portal-nav-link @if(request()->routeIs('docente.recompensas.*')) active @endif">
                                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                                </svg>
                                <span>Recompensas</span>
                            </a>
                        </li>

                    @elseif ($isOrientador)
                        <!-- Menú exclusivo para Orientadores Psicológicos Autorizados -->
                        <li>
                            <a href="{{ route('docente.citas.index') }}" class="portal-nav-link @if(request()->routeIs('docente.citas.index*') || request()->routeIs('docente.citas.show*')) active @endif">
                                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                    <line x1="16" y1="2" x2="16" y2="6"/>
                                    <line x1="8" y1="2" x2="8" y2="6"/>
                                    <line x1="3" y1="10" x2="21" y2="10"/>
                                </svg>
                                <span>Agenda de Citas</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('docente.citas.disponibilidad') }}" class="portal-nav-link @if(request()->routeIs('docente.citas.disponibilidad*')) active @endif">
                                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"/>
                                    <polyline points="12 6 12 12 16 14"/>
                                </svg>
                                <span>Horarios de Atención</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.meditaciones.index') }}" class="portal-nav-link @if(request()->routeIs('admin.meditaciones.*')) active @endif">
                                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"/>
                                    <path d="M12 6a6 6 0 016 6c0 3-3 6-6 6s-6-3-6-6a6 6 0 016-6z"/>
                                </svg>
                                <span>Gestión Meditaciones</span>
                            </a>
                        </li>
                    @endif
                </ul>
            </nav>

            <div class="portal-sidebar-footer">
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="portal-logout-btn" title="Cerrar sesión de forma segura">
                        <svg class="nav-icon" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/>
                            <polyline points="16 17 21 12 16 7"/>
                            <line x1="21" y1="12" x2="9" y2="12"/>
                        </svg>
                        <span>Cerrar sesión</span>
                    </button>
                </form>
            </div>
        </aside>

        <!-- Fondo oscurecido para versión móvil -->
        <div class="portal-backdrop d-lg-none" id="portalBackdrop"></div>

        <!-- Área principal de trabajo -->
        <div class="portal-main-wrapper">
            <!-- Barra superior limpia -->
            <header class="portal-topbar">
                <div class="portal-topbar-left">
                    <button type="button" class="portal-menu-toggle d-lg-none" id="sidebarToggle" aria-label="Abrir menú de navegación" aria-controls="portalSidebar" aria-expanded="false">
                        <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="3" y1="12" x2="21" y2="12"/>
                            <line x1="3" y1="6" x2="21" y2="6"/>
                            <line x1="3" y1="18" x2="21" y2="18"/>
                        </svg>
                    </button>
                    <span class="portal-breadcrumb d-none d-md-inline-block text-muted">
                        Bienestar UPEMOR &bull; <strong>{{ $roleName }}</strong>
                    </span>
                </div>

                <div class="portal-topbar-right">
                    @if ($isAlumno && $user?->estudiante)
                        <div class="d-none d-sm-inline-flex align-items-center gap-1.5 px-3 py-1.5 rounded-pill bg-purple-subtle text-purple-emphasis border border-purple-subtle small fw-bold me-2" id="topbarPuntosBadge" title="Tus puntos de bienestar acumulados">
                            <i class="bi bi-gem text-primary"></i>
                            <span id="topbarPuntosValor" class="user-points-badge">{{ $user->estudiante->saldo_puntos }}</span> pts
                        </div>
                    @endif

                    <!-- Icono de notificaciones con dropdown funcional Bootstrap 5 -->
                    <div class="dropdown portal-notif-dropdown">
                        <button type="button" class="portal-notif-btn btn border-0 bg-transparent p-0 position-relative"
                                id="notifMenuDropdown" data-bs-toggle="dropdown" aria-expanded="false"
                                aria-label="Notificaciones" title="{{ $totalNoLeidas > 0 ? $totalNoLeidas . ' notificaciones sin leer' : 'Sin notificaciones pendientes' }}">
                            <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                                <path d="M13.73 21a2 2 0 01-3.46 0"/>
                            </svg>
                            <span id="topbarNotifBadge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger {{ $totalNoLeidas > 0 ? '' : 'd-none' }}" style="font-size: 0.65rem; padding: 0.25em 0.5em;">
                                {{ $totalNoLeidas > 99 ? '99+' : $totalNoLeidas }}
                                <span class="visually-hidden">notificaciones no leídas</span>
                            </span>
                        </button>

                        <div class="dropdown-menu dropdown-menu-end shadow-lg rounded-4 border-0 p-0 mt-2" aria-labelledby="notifMenuDropdown" style="width: 350px; max-width: 92vw; border: 1px solid rgba(99, 102, 241, 0.2) !important;">
                            <div class="px-3 py-2.5 border-bottom d-flex align-items-center justify-content-between bg-white rounded-top-4">
                                <div class="d-flex align-items-center gap-1.5">
                                    <span class="fw-bold text-dark small">Notificaciones</span>
                                    <span id="notifDropdownCount" class="badge rounded-pill bg-primary ms-1 {{ $totalNoLeidas > 0 ? '' : 'd-none' }}" style="font-size: 0.65rem;">
                                        {{ $totalNoLeidas > 99 ? '99+' : $totalNoLeidas }}
                                    </span>
                                </div>
                                <form id="notifDropdownMarkAllForm" action="{{ route('notificaciones.marcar-todas') }}" method="POST" class="m-0 {{ $totalNoLeidas > 0 ? '' : 'd-none' }}">
                                    @csrf
                                    <button type="submit" class="btn btn-link p-0 text-decoration-none small text-primary fw-semibold" style="font-size: 0.72rem;">
                                        <i class="bi bi-check2-all me-0.5"></i>Marcar todas leídas
                                    </button>
                                </form>
                            </div>

                            <div id="notifDropdownList" class="list-group list-group-flush" style="max-height: 360px; overflow-y: auto;">
                                @forelse ($notificacionesRecientes as $nr)
                                    @php
                                        $nrNoLeida = is_null($nr->read_at);
                                        $nrData = is_array($nr->data) ? $nr->data : [];
                                        $nrTipo = $nrData['tipo'] ?? 'general';
                                        $nrTitulo = $nrData['titulo'] ?? 'Notificación';
                                        $nrMensaje = $nrData['mensaje'] ?? '';
                                    @endphp
                                    <div class="list-group-item px-3 py-2.5 notif-dropdown-item {{ $nrNoLeida ? 'is-unread es-no-leida' : 'is-read es-leida' }}"
                                         id="dropdown-notif-{{ $nr->id }}"
                                         data-notification-id="{{ $nr->id }}"
                                         data-unread="{{ $nrNoLeida ? 'true' : 'false' }}"
                                         style="{{ $nrNoLeida ? 'background-color: #f3f0ff !important; border-left: 4px solid #6366f1 !important; border-bottom: 1px solid #e0e7ff !important;' : 'background-color: #ffffff !important; border-left: 4px solid transparent !important; border-bottom: 1px solid #f1f5f9 !important;' }}">
                                        <div class="d-flex align-items-start gap-2">
                                            <span class="notif-unread-dot mt-1.5 {{ $nrNoLeida ? '' : 'd-none' }}"
                                                  title="Notificación no leída" aria-hidden="true"></span>
                                            <div class="flex-grow-1 min-w-0">
                                                <div class="d-flex align-items-center justify-content-between gap-1 mb-1">
                                                    <a href="{{ route('notificaciones.ir', $nr->id) }}"
                                                       class="text-decoration-none text-truncate d-inline-block notif-item-title {{ $nrNoLeida ? 'fw-bold text-dark' : 'fw-normal text-secondary' }}"
                                                       style="font-size: 0.82rem; max-width: 195px;">
                                                        {{ $nrTitulo }}
                                                    </a>
                                                    <span class="badge rounded-pill notif-state-badge {{ $nrNoLeida ? 'bg-primary text-white' : 'bg-light text-secondary border' }}" style="font-size: 0.62rem;">
                                                        {{ $nrNoLeida ? 'No leída' : 'Leída' }}
                                                    </span>
                                                </div>
                                                <a href="{{ route('notificaciones.ir', $nr->id) }}" class="text-decoration-none d-block mb-1">
                                                    <p class="small mb-0 text-truncate notif-item-message {{ $nrNoLeida ? 'text-dark fw-medium' : 'text-muted' }}" style="font-size: 0.75rem; max-width: 280px;">
                                                        {{ $nrMensaje }}
                                                    </p>
                                                </a>
                                                <div class="d-flex align-items-center justify-content-between mt-1">
                                                    <span class="text-muted notif-item-time" style="font-size: 0.68rem;">{{ $nr->created_at?->diffForHumans(null, true, true) ?? 'Ahora' }}</span>
                                                    @if ($nrNoLeida)
                                                        <button type="button"
                                                                class="btn btn-link p-0 text-decoration-none fw-semibold text-primary btn-dropdown-mark-read"
                                                                style="font-size: 0.7rem;"
                                                                data-id="{{ $nr->id }}"
                                                                data-url="{{ route('notificaciones.leer', $nr->id) }}"
                                                                title="Marcar como leída sin salir de la página">
                                                            <i class="bi bi-check2-circle me-0.5"></i>Marcar leída
                                                        </button>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="px-3 py-4 text-center text-muted" id="notifDropdownEmpty">
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="mb-1 text-muted">
                                            <path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                                            <path d="M13.73 21a2 2 0 01-3.46 0"/>
                                        </svg>
                                        <div class="small fw-semibold">Sin notificaciones</div>
                                        <div class="text-muted" style="font-size: 0.72rem;">No tienes avisos pendientes</div>
                                    </div>
                                @endforelse
                            </div>

                            <div class="px-3 py-2 text-center border-top bg-light rounded-bottom-4">
                                <a href="{{ route('notificaciones.index') }}" class="small fw-semibold text-decoration-none text-primary" style="font-size: 0.78rem;">
                                    Ver todas las notificaciones &rarr;
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Perfil del usuario con dropdown Bootstrap 5 -->
                    <div class="dropdown portal-user-dropdown">
                        <button class="portal-user-profile btn border-0 bg-transparent p-0 d-flex align-items-center dropdown-toggle" type="button" id="userMenuDropdown" data-bs-toggle="dropdown" aria-expanded="false" style="box-shadow: none;">
                            <div class="portal-user-avatar" aria-hidden="true">
                                <svg viewBox="0 0 36 36" width="38" height="38" fill="none">
                                    <circle cx="18" cy="18" r="18" fill="#e9ddff" />
                                    <circle cx="18" cy="14" r="6" fill="#6f42c1" />
                                    <path d="M7 32C7 25.5 12 23 18 23C24 23 29 25.5 29 32" fill="#56329a" />
                                </svg>
                            </div>
                            <div class="portal-user-info d-none d-sm-block text-start ms-2">
                                <span class="portal-user-name d-block fw-semibold" style="font-size: 0.88rem; color: #1e1b4b; line-height: 1.2;">{{ $nombreCompleto }}</span>
                                <span class="portal-user-role d-block text-muted" style="font-size: 0.75rem;">{{ $roleName }}</span>
                            </div>
                            <span class="portal-user-chevron ms-1 text-muted" aria-hidden="true">
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5">
                                    <polyline points="6 9 12 15 18 9"/>
                                </svg>
                            </span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm rounded-4 border-0 p-2 mt-2" aria-labelledby="userMenuDropdown" style="min-width: 230px; border: 1px solid rgba(139, 92, 246, 0.12) !important;">
                            <li class="px-3 py-2 border-bottom mb-1">
                                <span class="d-block small fw-bold text-dark">{{ $nombreCompleto }}</span>
                                <span class="d-block text-muted" style="font-size: 0.74rem;">{{ $usuario->correo ?? '' }}</span>
                                <span class="badge rounded-pill px-2 py-1 mt-1" style="font-size: 0.68rem; background: #ede9fe; color: #5b21b6;">{{ $roleName }}</span>
                            </li>
                            <li>
                                <a class="dropdown-item rounded-3 small py-2 d-flex align-items-center gap-2" href="{{ $isAlumno ? route('alumno.dashboard') : ($isAdmin ? route('admin.dashboard') : route('docente.dashboard')) }}">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M3 9.5L12 3l9 6.5V20a1 1 0 01-1 1h-5v-6H9v6H4a1 1 0 01-1-1V9.5z"/>
                                    </svg>
                                    <span>Panel principal</span>
                                </a>
                            </li>
                            <li><hr class="dropdown-divider my-1"></li>
                            <li>
                                <form action="{{ route('logout') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="dropdown-item rounded-3 small py-2 text-danger d-flex align-items-center gap-2">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/>
                                            <polyline points="16 17 21 12 16 7"/>
                                            <line x1="21" y1="12" x2="9" y2="12"/>
                                        </svg>
                                        <span>Cerrar sesión</span>
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </header>

            <!-- Contenido específico de la página -->
            <main class="portal-content">
                {{-- Notificaciones temporales y persistentes accesibles --}}
                <x-portal-alerts />

                @yield('content')
            </main>
        </div>
    </div>

    @stack('scripts')
    <script>
        (function() {
            // 1. Manejador accesible del menú responsivo para teléfonos y tabletas
            const sidebar = document.getElementById('portalSidebar');
            const toggle = document.getElementById('sidebarToggle');
            const backdrop = document.getElementById('portalBackdrop');

            function openSidebar() {
                sidebar?.classList.add('open');
                backdrop?.classList.add('show');
                toggle?.setAttribute('aria-expanded', 'true');
            }

            function closeSidebar() {
                sidebar?.classList.remove('open');
                backdrop?.classList.remove('show');
                toggle?.setAttribute('aria-expanded', 'false');
            }

            function toggleSidebar() {
                const isOpen = sidebar?.classList.contains('open');
                if (isOpen) {
                    closeSidebar();
                } else {
                    openSidebar();
                }
            }

            toggle?.addEventListener('click', toggleSidebar);
            backdrop?.addEventListener('click', closeSidebar);

            // Cerrar menú con la tecla Escape
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && sidebar?.classList.contains('open')) {
                    closeSidebar();
                    toggle?.focus();
                }
            });

            // Cerrar menú móvil al hacer clic en un enlace de navegación
            sidebar?.querySelectorAll('.portal-nav-link:not(.portal-link-disabled)').forEach(function(link) {
                link.addEventListener('click', function() {
                    if (window.innerWidth < 992) {
                        closeSidebar();
                    }
                });
            });

            // 2. Manejador de Notificaciones Temporales con salida suave
            function setupAutoDismissAlerts() {
                const alerts = document.querySelectorAll('.portal-auto-dismiss[data-auto-dismiss]');
                
                alerts.forEach(function(alertEl) {
                    if (alertEl.dataset.timerActive === 'true') return;
                    alertEl.dataset.timerActive = 'true';

                    const delay = parseInt(alertEl.dataset.autoDismiss, 10) || 3000;
                    let timeoutId = null;
                    let isPaused = false;

                    function startDismissTimer(ms) {
                        if (timeoutId) clearTimeout(timeoutId);
                        timeoutId = setTimeout(function() {
                            dismissAlertWithTransition(alertEl);
                        }, ms);
                    }

                    function dismissAlertWithTransition(el) {
                        if (!document.body.contains(el)) return;
                        
                        // Añadir clase de transición suave de salida
                        el.classList.add('portal-alert-closing');

                        // Esperar a que la transición CSS termine (300ms) para cerrar el alert
                        setTimeout(function() {
                            if (window.bootstrap && window.bootstrap.Alert) {
                                const bsAlert = window.bootstrap.Alert.getOrCreateInstance(el);
                                bsAlert.close();
                            } else {
                                el.remove();
                            }
                        }, 300);
                    }

                    // Iniciar el temporizador automático
                    startDismissTimer(delay);

                    // Pausar si el usuario sitúa el mouse encima (para leer con calma)
                    alertEl.addEventListener('mouseenter', function() {
                        if (timeoutId) clearTimeout(timeoutId);
                        isPaused = true;
                    });

                    // Reanudar con tiempo prudente al retirar el mouse
                    alertEl.addEventListener('mouseleave', function() {
                        if (isPaused) {
                            isPaused = false;
                            startDismissTimer(1800);
                        }
                    });

                    // Pausar si recibe foco de teclado (accesibilidad para lectores de pantalla)
                    alertEl.addEventListener('focusin', function() {
                        if (timeoutId) clearTimeout(timeoutId);
                        isPaused = true;
                    });

                    alertEl.addEventListener('focusout', function() {
                        if (isPaused) {
                            isPaused = false;
                            startDismissTimer(1800);
                        }
                    });
                });
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', setupAutoDismissAlerts);
            } else {
                setupAutoDismissAlerts();
            }

            // 3. Control de Inactividad de Sesión y Advertencia Previa
            (function setupSessionTimeoutWarning() {
                const WARNING_TIME_MS = 13 * 60 * 1000; // 13 minutos de inactividad para mostrar aviso previo
                const EXPIRATION_TIME_MS = 15 * 60 * 1000; // 15 minutos timeout límite estricto (900,000 ms)
                let lastInteractionTime = Date.now();
                let warningModalInstance = null;
                let countdownIntervalId = null;
                let isWarningShowing = false;
                let isExpiredShowing = false;

                // Sincronización multi-pestaña con localStorage
                try {
                    const savedTime = localStorage.getItem('bienestar_last_activity');
                    if (savedTime && (Date.now() - parseInt(savedTime)) < EXPIRATION_TIME_MS) {
                        lastInteractionTime = parseInt(savedTime);
                    } else {
                        localStorage.setItem('bienestar_last_activity', lastInteractionTime.toString());
                    }

                    window.addEventListener('storage', function(e) {
                        if (e.key === 'bienestar_last_activity' && e.newValue) {
                            lastInteractionTime = parseInt(e.newValue);
                        }
                        if (e.key === 'bienestar_session_expired' && e.newValue) {
                            showExpiredModal();
                        }
                    });
                } catch (e) {}

                function recordUserInteraction() {
                    if (!isWarningShowing && !isExpiredShowing) {
                        lastInteractionTime = Date.now();
                        try {
                            localStorage.setItem('bienestar_last_activity', lastInteractionTime.toString());
                        } catch (e) {}
                    }
                }

                // Escuchar eventos globales de interacción real del usuario
                ['mousemove', 'mousedown', 'keydown', 'touchstart', 'scroll'].forEach(function(evt) {
                    window.addEventListener(evt, recordUserInteraction, { passive: true });
                });

                // Detectar recuperación de pestañas suspendidas o en segundo plano
                document.addEventListener('visibilitychange', function() {
                    if (document.visibilityState === 'visible') {
                        const elapsed = Date.now() - lastInteractionTime;
                        if (elapsed >= EXPIRATION_TIME_MS) {
                            showExpiredModal();
                        }
                    }
                });

                const modalEl = document.getElementById('modalAvisoInactividad');
                const btnContinuar = document.getElementById('btnContinuarSesion');
                const contadorSegundos = document.getElementById('contadorInactividadSegundos');
                const expiredModalEl = document.getElementById('modalSesionFinalizadaInactividad');

                function showExpiredModal() {
                    if (isExpiredShowing) return;
                    isExpiredShowing = true;
                    if (countdownIntervalId) clearInterval(countdownIntervalId);

                    try {
                        localStorage.setItem('bienestar_session_expired', Date.now().toString());
                    } catch (e) {}

                    if (warningModalInstance) {
                        try { warningModalInstance.hide(); } catch (e) {}
                    }

                    if (expiredModalEl && window.bootstrap && window.bootstrap.Modal) {
                        const expiredModal = window.bootstrap.Modal.getOrCreateInstance(expiredModalEl, {
                            backdrop: 'static',
                            keyboard: false
                        });
                        expiredModal.show();
                    } else {
                        window.location.href = "{{ route('login') }}?expired=1";
                    }
                }

                // Exponer globalmente para invocar desde respuestas de polling con sesión expirada
                window.showExpiredSessionModal = showExpiredModal;

                if (!modalEl || !btnContinuar || !contadorSegundos) return;

                function showWarningModal() {
                    if (isWarningShowing || isExpiredShowing) return;
                    isWarningShowing = true;

                    if (window.bootstrap && window.bootstrap.Modal) {
                        warningModalInstance = window.bootstrap.Modal.getOrCreateInstance(modalEl, {
                            backdrop: 'static',
                            keyboard: false
                        });
                        warningModalInstance.show();
                    }

                    let remainingSeconds = Math.max(0, Math.floor((EXPIRATION_TIME_MS - (Date.now() - lastInteractionTime)) / 1000));
                    contadorSegundos.textContent = remainingSeconds;

                    if (countdownIntervalId) clearInterval(countdownIntervalId);
                    countdownIntervalId = setInterval(function() {
                        remainingSeconds--;
                        contadorSegundos.textContent = Math.max(0, remainingSeconds);

                        if (remainingSeconds <= 0) {
                            clearInterval(countdownIntervalId);
                            showExpiredModal();
                        }
                    }, 1000);
                }

                btnContinuar.addEventListener('click', function() {
                    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
                    const token = csrfMeta ? csrfMeta.getAttribute('content') : '';

                    fetch("{{ route('session.keep-alive') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': token,
                            'Accept': 'application/json'
                        }
                    }).then(function() {
                        if (countdownIntervalId) clearInterval(countdownIntervalId);
                        isWarningShowing = false;
                        lastInteractionTime = Date.now();
                        try {
                            localStorage.setItem('bienestar_last_activity', lastInteractionTime.toString());
                        } catch (e) {}
                        if (warningModalInstance) {
                            warningModalInstance.hide();
                        }
                    }).catch(function() {
                        if (countdownIntervalId) clearInterval(countdownIntervalId);
                        isWarningShowing = false;
                        lastInteractionTime = Date.now();
                        try {
                            localStorage.setItem('bienestar_last_activity', lastInteractionTime.toString());
                        } catch (e) {}
                        if (warningModalInstance) {
                            warningModalInstance.hide();
                        }
                    });
                });

                // Comprobación periódica cada 10 segundos
                setInterval(function() {
                    const elapsed = Date.now() - lastInteractionTime;
                    if (elapsed >= EXPIRATION_TIME_MS) {
                        showExpiredModal();
                    } else if (elapsed >= WARNING_TIME_MS && !isWarningShowing) {
                        showWarningModal();
                    }
                }, 10000);
            })();
        })();
    </script>

    <!-- Modal Accesible de Advertencia de Inactividad de Sesión -->
    <div class="modal fade" id="modalAvisoInactividad" tabindex="-1" role="dialog" aria-labelledby="modalInactividadLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content shadow border-0">
                <div class="modal-header bg-warning-subtle text-dark border-bottom border-warning-subtle">
                    <h5 class="modal-title fw-bold" id="modalInactividadLabel">
                        <i class="bi bi-clock-history text-warning me-2" aria-hidden="true"></i>
                        Aviso de Inactividad de Sesión
                    </h5>
                </div>
                <div class="modal-body py-4">
                    <p class="text-secondary mb-3">
                        Tu sesión institucional en <strong>Bienestar UPEMOR</strong> está a punto de expirar por inactividad.
                    </p>
                    <div class="alert alert-light border d-flex align-items-center mb-0" role="alert">
                        <i class="bi bi-shield-exclamation fs-3 text-warning me-3" aria-hidden="true"></i>
                        <div>
                            Por seguridad y protección de tus datos, la sesión se cerrará automáticamente en:
                            <div class="fs-4 fw-bold text-danger mt-1">
                                <span id="contadorInactividadSegundos">120</span> segundos
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-top">
                    <form action="{{ route('logout') }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-outline-secondary">Cerrar Sesión Ahora</button>
                    </form>
                    <button type="button" class="btn btn-primary fw-semibold" id="btnContinuarSesion">
                        <i class="bi bi-arrow-repeat me-1" aria-hidden="true"></i>
                        Mantener Sesión Activa
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Institucional: Sesión Finalizada por Inactividad (FF.4) -->
    <div class="modal fade" id="modalSesionFinalizadaInactividad" tabindex="-1" role="dialog" aria-labelledby="modalSesionFinalizadaLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content shadow-lg border-0 rounded-4 overflow-hidden">
                <div class="modal-header bg-danger-subtle text-danger border-bottom border-danger-subtle">
                    <h5 class="modal-title fw-bold" id="modalSesionFinalizadaLabel">
                        <i class="bi bi-shield-lock-fill text-danger me-2" aria-hidden="true"></i>
                        Sesión finalizada por inactividad
                    </h5>
                </div>
                <div class="modal-body py-4 px-4">
                    <p class="text-secondary mb-3">
                        Por tu seguridad y para proteger la confidencialidad de tu información, tu sesión ha finalizado después de 15 minutos sin actividad. Para continuar, vuelve a iniciar sesión.
                    </p>
                </div>
                <div class="modal-footer bg-light border-top justify-content-end">
                    <a href="{{ route('login') }}?expired=1" class="btn btn-primary rounded-pill px-4 fw-semibold" id="btnAceptarSesionFinalizada">
                        <i class="bi bi-box-arrow-in-right me-1" aria-hidden="true"></i>
                        Aceptar e iniciar sesión
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Flotante Institucional para Notificaciones en Vivo (5 segundos, próximo a la campana superior) -->
    <div id="liveNotificationToastContainer" class="toast-container position-fixed p-0" style="top: 68px; right: 18px; z-index: 1095; width: 350px; max-width: calc(100vw - 32px);">
        <div id="liveNotificationToast"
             class="toast text-bg-white bg-white border-0 shadow-lg rounded-4 overflow-hidden w-100"
             role="status"
             aria-live="polite"
             aria-atomic="true"
             data-bs-autohide="true"
             data-bs-delay="5000"
             style="border: 1px solid rgba(99, 102, 241, 0.28) !important; border-left: 5px solid #6366f1 !important;">
            <div class="d-flex align-items-start p-3">
                <div id="toastNotificationIconWrap" class="rounded-circle bg-primary-subtle text-primary p-2 flex-shrink-0 d-flex align-items-center justify-content-center me-2.5" style="width: 38px; height: 38px;">
                    <i id="toastNotificationIcon" class="bi bi-bell-fill fs-6"></i>
                </div>
                <div class="flex-grow-1 min-w-0 pe-2">
                    <div class="d-flex align-items-center justify-content-between gap-2 mb-0.5">
                        <span id="toastNotificationCategory" class="badge rounded-pill bg-primary-subtle text-primary border border-primary-subtle" style="font-size: 0.62rem;">Nuevo aviso</span>
                        <span id="toastNotificationTime" class="text-muted" style="font-size: 0.68rem;">Ahora</span>
                    </div>
                    <strong class="d-block text-dark small mb-0.5 text-truncate" id="toastNotificationTitle">Nueva notificación</strong>
                    <p class="small text-secondary mb-1.5 text-break" id="toastNotificationBody" style="font-size: 0.78rem; line-height: 1.35;"></p>
                    <div class="d-flex align-items-center gap-3">
                        <a href="{{ route('notificaciones.index') }}" id="toastNotificationLink" class="small text-primary text-decoration-none fw-bold" style="font-size: 0.76rem;">
                            Consultar notificación &rarr;
                        </a>
                    </div>
                </div>
                <button type="button" class="btn-close flex-shrink-0 mt-0.5" data-bs-dismiss="toast" aria-label="Cerrar aviso"></button>
            </div>
            <div class="progress rounded-0" style="height: 3px; background-color: #eef2ff;">
                <div id="toastProgressBar" class="progress-bar bg-primary" role="progressbar" style="width: 100%; transition: width 5s linear;" aria-hidden="true"></div>
            </div>
        </div>
    </div>

    <!-- Script de Sondeo Asíncrono, Cola de Toasts (5s) y Sincronización Global de Notificaciones -->
    <script>
        (function() {
            @auth
            const currentUserId = "{{ Auth::id() }}";
            const pollIntervalMs = 15000;
            const TOAST_DURATION_MS = 5000;
            const pollUrl = "{{ route('notificaciones.recientes-unread') }}";
            const notificacionesIndexUrl = "{{ route('notificaciones.index') }}";
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || "{{ csrf_token() }}";

            const sessionInitKey = 'bienestar_notif_session_init_' + currentUserId;
            const seenIdsKey = 'bienestar_seen_notif_ids_' + currentUserId;
            const initialUnreadItems = @json($notificacionesNoLeidasRecientes);

            let pollTimerId = null;
            let toastQueue = [];
            let isShowingToast = false;
            let manualToastTimeoutId = null;

            function getSeenIds() {
                try {
                    const raw = sessionStorage.getItem(seenIdsKey);
                    if (!raw) return [];
                    const parsed = JSON.parse(raw);
                    return Array.isArray(parsed) ? parsed.map(String) : [];
                } catch (e) {
                    return [];
                }
            }

            function saveSeenIds(ids) {
                try {
                    const unique = Array.from(new Set(ids.map(String))).slice(-100);
                    sessionStorage.setItem(seenIdsKey, JSON.stringify(unique));
                } catch (e) {}
            }

            function escapeHtml(str) {
                const div = document.createElement('div');
                div.textContent = str == null ? '' : String(str);
                return div.innerHTML;
            }

            function getIconConfigForType(tipo) {
                switch (String(tipo || 'general').toLowerCase()) {
                    case 'recompensa':
                    case 'propuesta':
                        return { icon: 'bi-award-fill', label: 'Recompensas' };
                    case 'cita':
                        return { icon: 'bi-calendar-check-fill', label: 'Citas y Orientación' };
                    case 'evaluacion':
                        return { icon: 'bi-clipboard2-pulse-fill', label: 'Tests y Evaluaciones' };
                    case 'meditacion':
                        return { icon: 'bi-flower1', label: 'Meditación y Calma' };
                    case 'seguimiento':
                        return { icon: 'bi-journal-richtext', label: 'Seguimiento Diario' };
                    case 'encuesta':
                        return { icon: 'bi-ui-checks-grid', label: 'Encuestas' };
                    default:
                        return { icon: 'bi-bell-fill', label: 'Aviso Institucional' };
                }
            }

            // Encolar nuevas notificaciones sin duplicados y mostrarlas durante 5 segundos sin marcarlas como leídas
            function enqueueToastNotifications(items) {
                if (!Array.isArray(items) || items.length === 0) return;

                let seenIds = getSeenIds();
                // Procesar de más antigua a más reciente para que aparezcan en orden de llegada
                const ordered = items.slice().reverse();
                let changed = false;

                ordered.forEach(function(item) {
                    if (!item || !item.id) return;
                    const idStr = String(item.id);
                    if (!seenIds.includes(idStr)) {
                        seenIds.push(idStr);
                        changed = true;
                        if (!toastQueue.some(q => String(q.id) === idStr)) {
                            toastQueue.push(item);
                        }
                    }
                });

                if (changed) {
                    saveSeenIds(seenIds);
                }

                processToastQueue();
            }

            function processToastQueue() {
                if (isShowingToast || toastQueue.length === 0) return;

                const nextItem = toastQueue.shift();
                if (!nextItem) return;

                const toastEl = document.getElementById('liveNotificationToast');
                if (!toastEl) return;

                isShowingToast = true;

                const titleEl = document.getElementById('toastNotificationTitle');
                const bodyEl = document.getElementById('toastNotificationBody');
                const linkEl = document.getElementById('toastNotificationLink');
                const timeEl = document.getElementById('toastNotificationTime');
                const catEl = document.getElementById('toastNotificationCategory');
                const iconEl = document.getElementById('toastNotificationIcon');
                const progressEl = document.getElementById('toastProgressBar');

                const iconCfg = getIconConfigForType(nextItem.tipo);

                if (titleEl) titleEl.textContent = nextItem.titulo || 'Nueva notificación institucional';
                if (bodyEl) bodyEl.textContent = nextItem.toast_mensaje || nextItem.mensaje || '';
                if (linkEl) linkEl.href = nextItem.url || notificacionesIndexUrl;
                if (timeEl) timeEl.textContent = nextItem.created_at || 'Ahora';
                if (catEl) catEl.textContent = iconCfg.label;
                if (iconEl) iconEl.className = 'bi ' + iconCfg.icon + ' fs-6';

                const prefersReducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                if (progressEl) {
                    progressEl.style.transition = 'none';
                    progressEl.style.width = '100%';
                    if (!prefersReducedMotion) {
                        void progressEl.offsetWidth;
                        progressEl.style.transition = 'width ' + (TOAST_DURATION_MS / 1000) + 's linear';
                        progressEl.style.width = '0%';
                    }
                }

                if (manualToastTimeoutId) {
                    clearTimeout(manualToastTimeoutId);
                    manualToastTimeoutId = null;
                }

                const onToastHidden = function() {
                    toastEl.removeEventListener('hidden.bs.toast', onToastHidden);
                    if (manualToastTimeoutId) {
                        clearTimeout(manualToastTimeoutId);
                        manualToastTimeoutId = null;
                    }
                    isShowingToast = false;
                    if (toastQueue.length > 0) {
                        setTimeout(processToastQueue, 250);
                    }
                };

                if (window.bootstrap && window.bootstrap.Toast) {
                    toastEl.addEventListener('hidden.bs.toast', onToastHidden);
                    const bsToast = bootstrap.Toast.getOrCreateInstance(toastEl, {
                        autohide: true,
                        delay: TOAST_DURATION_MS
                    });
                    bsToast.show();
                    // Respaldo exacto de 5 segundos en caso de que el foco del navegador retenga el toast
                    manualToastTimeoutId = setTimeout(function() {
                        try { bsToast.hide(); } catch (e) {}
                    }, TOAST_DURATION_MS + 150);
                } else {
                    toastEl.classList.add('show');
                    manualToastTimeoutId = setTimeout(function() {
                        toastEl.classList.remove('show');
                        onToastHidden();
                    }, TOAST_DURATION_MS);
                }
            }

            // Actualizar todos los contadores de notificaciones no leídas en la interfaz
            function updateCounters(unreadCount) {
                const count = Math.max(0, parseInt(unreadCount, 10) || 0);
                const textVal = count > 99 ? '99+' : String(count);

                const topBadge = document.getElementById('topbarNotifBadge');
                const bellBtn = document.getElementById('notifMenuDropdown');
                if (topBadge) {
                    topBadge.innerHTML = escapeHtml(textVal) + '<span class="visually-hidden">notificaciones no leídas</span>';
                    topBadge.classList.toggle('d-none', count === 0);
                }
                if (bellBtn) {
                    bellBtn.setAttribute('title', count > 0 ? count + ' notificaciones sin leer' : 'Sin notificaciones pendientes');
                }

                const dropCount = document.getElementById('notifDropdownCount');
                if (dropCount) {
                    dropCount.textContent = textVal;
                    dropCount.classList.toggle('d-none', count === 0);
                }

                const markAllForm = document.getElementById('notifDropdownMarkAllForm');
                if (markAllForm) {
                    markAllForm.classList.toggle('d-none', count === 0);
                }

                const tabNoLeidas = document.getElementById('tabNoLeidasCount');
                if (tabNoLeidas) {
                    tabNoLeidas.textContent = String(count);
                }
            }

            // Renderizar los ítems del dropdown de la campana conservando el resaltado de no leídas
            function renderDropdownItems(items) {
                const listEl = document.getElementById('notifDropdownList');
                if (!listEl || !Array.isArray(items)) return;

                if (items.length === 0) {
                    listEl.innerHTML = `
                        <div class="px-3 py-4 text-center text-muted" id="notifDropdownEmpty">
                            <div class="small fw-semibold">Sin notificaciones</div>
                            <div class="text-muted" style="font-size: 0.72rem;">No tienes avisos pendientes</div>
                        </div>
                    `;
                    return;
                }

                listEl.innerHTML = items.map(function(item) {
                    const isUnread = Boolean(item.is_unread);
                    const idEsc = escapeHtml(item.id);
                    const tituloEsc = escapeHtml(item.titulo || 'Notificación');
                    const mensajeEsc = escapeHtml(item.mensaje || '');
                    const urlEsc = escapeHtml(item.url || notificacionesIndexUrl);
                    const leerUrlEsc = escapeHtml(item.leer_url || ('/notificaciones/' + item.id + '/leer'));
                    const timeEsc = escapeHtml(item.created_at || 'Ahora');

                    const rowClass = isUnread ? 'is-unread es-no-leida' : 'is-read es-leida';
                    const rowStyle = isUnread
                        ? 'background-color: #f3f0ff !important; border-left: 4px solid #6366f1 !important; border-bottom: 1px solid #e0e7ff !important;'
                        : 'background-color: #ffffff !important; border-left: 4px solid transparent !important; border-bottom: 1px solid #f1f5f9 !important;';
                    const dotClass = isUnread ? '' : 'd-none';
                    const titleClass = isUnread ? 'fw-bold text-dark' : 'fw-normal text-secondary';
                    const msgClass = isUnread ? 'text-dark fw-medium' : 'text-muted';
                    const badgeClass = isUnread ? 'bg-primary text-white' : 'bg-light text-secondary border';
                    const badgeText = isUnread ? 'No leída' : 'Leída';

                    const markBtnHtml = isUnread ? `
                        <button type="button"
                                class="btn btn-link p-0 text-decoration-none fw-semibold text-primary btn-dropdown-mark-read"
                                style="font-size: 0.7rem;"
                                data-id="${idEsc}"
                                data-url="${leerUrlEsc}"
                                title="Marcar como leída sin salir de la página">
                            <i class="bi bi-check2-circle me-0.5"></i>Marcar leída
                        </button>
                    ` : '';

                    return `
                        <div class="list-group-item px-3 py-2.5 notif-dropdown-item ${rowClass}"
                             id="dropdown-notif-${idEsc}"
                             data-notification-id="${idEsc}"
                             data-unread="${isUnread ? 'true' : 'false'}"
                             style="${rowStyle}">
                            <div class="d-flex align-items-start gap-2">
                                <span class="notif-unread-dot mt-1.5 ${dotClass}" title="Notificación no leída" aria-hidden="true"></span>
                                <div class="flex-grow-1 min-w-0">
                                    <div class="d-flex align-items-center justify-content-between gap-1 mb-1">
                                        <a href="${urlEsc}"
                                           class="text-decoration-none text-truncate d-inline-block notif-item-title ${titleClass}"
                                           style="font-size: 0.82rem; max-width: 195px;">
                                            ${tituloEsc}
                                        </a>
                                        <span class="badge rounded-pill notif-state-badge ${badgeClass}" style="font-size: 0.62rem;">
                                            ${badgeText}
                                        </span>
                                    </div>
                                    <a href="${urlEsc}" class="text-decoration-none d-block mb-1">
                                        <p class="small mb-0 text-truncate notif-item-message ${msgClass}" style="font-size: 0.75rem; max-width: 280px;">
                                            ${mensajeEsc}
                                        </p>
                                    </a>
                                    <div class="d-flex align-items-center justify-content-between mt-1">
                                        <span class="text-muted notif-item-time" style="font-size: 0.68rem;">${timeEsc}</span>
                                        ${markBtnHtml}
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                }).join('');
            }

            // Aplicar estado visual de "Leída" a un ítem específico en el dropdown y en la página de notificaciones
            function markItemVisuallyAsRead(notifId) {
                const dropItem = document.getElementById('dropdown-notif-' + notifId);
                if (dropItem) {
                    dropItem.classList.remove('is-unread', 'es-no-leida');
                    dropItem.classList.add('is-read', 'es-leida');
                    dropItem.setAttribute('data-unread', 'false');
                    dropItem.style.cssText = 'background-color: #ffffff !important; border-left: 4px solid transparent !important; border-bottom: 1px solid #f1f5f9 !important;';

                    const dot = dropItem.querySelector('.notif-unread-dot');
                    if (dot) dot.classList.add('d-none');

                    const title = dropItem.querySelector('.notif-item-title');
                    if (title) {
                        title.classList.remove('fw-bold', 'text-dark');
                        title.classList.add('fw-normal', 'text-secondary');
                    }

                    const msg = dropItem.querySelector('.notif-item-message');
                    if (msg) {
                        msg.classList.remove('text-dark', 'fw-medium');
                        msg.classList.add('text-muted');
                    }

                    const badge = dropItem.querySelector('.notif-state-badge');
                    if (badge) {
                        badge.className = 'badge rounded-pill notif-state-badge bg-light text-secondary border';
                        badge.textContent = 'Leída';
                    }

                    const btn = dropItem.querySelector('.btn-dropdown-mark-read');
                    if (btn) btn.remove();
                }

                const pageCard = document.getElementById('notif-card-' + notifId);
                if (pageCard) {
                    pageCard.classList.remove('border-start', 'border-4', 'border-primary', 'es-no-leida', 'is-unread');
                    pageCard.classList.add('es-leida', 'is-read', 'opacity-85');
                    pageCard.setAttribute('data-unread', 'false');
                    pageCard.style.background = '#ffffff';
                    pageCard.style.borderLeft = '4px solid transparent';

                    const dot = pageCard.querySelector('.notif-unread-dot');
                    if (dot) dot.classList.add('d-none');

                    const badgeNueva = pageCard.querySelector('.notif-badge-nueva');
                    if (badgeNueva) {
                        badgeNueva.className = 'badge rounded-pill bg-light text-secondary border notif-badge-leida';
                        badgeNueva.textContent = 'Leída';
                    }

                    const form = pageCard.querySelector('.form-marcar-leida');
                    const acciones = pageCard.querySelector('.notif-acciones');
                    if (form && acciones) {
                        form.remove();
                        if (!acciones.querySelector('.notif-leida-texto')) {
                            const spanLeida = document.createElement('span');
                            spanLeida.className = 'small text-muted notif-leida-texto';
                            spanLeida.style.fontSize = '0.72rem';
                            spanLeida.textContent = 'Leída recién';
                            acciones.appendChild(spanLeida);
                        }
                    }
                }
            }

            // Exponer API global para sincronización entre vistas
            window.BienestarNotificaciones = {
                syncAfterRead: function(notifId, unreadCount) {
                    markItemVisuallyAsRead(notifId);
                    updateCounters(unreadCount);
                },
                syncAfterMarkAll: function() {
                    document.querySelectorAll('.notif-dropdown-item[data-notification-id]').forEach(function(el) {
                        markItemVisuallyAsRead(el.getAttribute('data-notification-id'));
                    });
                    document.querySelectorAll('.notificacion-item[data-notification-id]').forEach(function(el) {
                        markItemVisuallyAsRead(el.getAttribute('data-notification-id'));
                    });
                    updateCounters(0);
                },
                enqueueToast: function(item) {
                    enqueueToastNotifications([item]);
                }
            };

            // Delegación de clic para el botón "Marcar leída" dentro del menú desplegable de la campana
            const dropdownListEl = document.getElementById('notifDropdownList');
            if (dropdownListEl) {
                dropdownListEl.addEventListener('click', function(e) {
                    const btn = e.target.closest('.btn-dropdown-mark-read');
                    if (!btn) return;
                    e.preventDefault();
                    e.stopPropagation();

                    if (btn.disabled) return;
                    btn.disabled = true;

                    const notifId = btn.getAttribute('data-id');
                    const actionUrl = btn.getAttribute('data-url');

                    fetch(actionUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify({})
                    })
                    .then(function(res) { return res.ok ? res.json() : Promise.reject(); })
                    .then(function(data) {
                        if (data && data.success) {
                            window.BienestarNotificaciones.syncAfterRead(notifId, data.unread_count);
                        } else {
                            btn.disabled = false;
                        }
                    })
                    .catch(function() {
                        btn.disabled = false;
                    });
                });
            }

            // Manejo asíncrono para "Marcar todas leídas" en el encabezado del dropdown
            const dropdownMarkAllForm = document.getElementById('notifDropdownMarkAllForm');
            if (dropdownMarkAllForm) {
                dropdownMarkAllForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    const actionUrl = dropdownMarkAllForm.getAttribute('action');

                    fetch(actionUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify({})
                    })
                    .then(function(res) { return res.ok ? res.json() : Promise.reject(); })
                    .then(function(data) {
                        if (data && data.success) {
                            window.BienestarNotificaciones.syncAfterMarkAll();
                        } else {
                            dropdownMarkAllForm.submit();
                        }
                    })
                    .catch(function() {
                        dropdownMarkAllForm.submit();
                    });
                });
            }

            // Inicialización de sesión de notificaciones al cargar la página:
            // - Si la sesión acaba de iniciar, registra las notificaciones preexistentes sin mostrar popups retroactivos.
            // - Si la sesión ya estaba activa y llegó una nueva notificación durante la navegación, muestra su toast de 5s.
            document.addEventListener('DOMContentLoaded', function() {
                const isInitialized = sessionStorage.getItem(sessionInitKey) === '1';
                if (!isInitialized) {
                    const initialIds = Array.isArray(initialUnreadItems) ? initialUnreadItems.map(i => String(i.id)) : [];
                    saveSeenIds(initialIds);
                    try { sessionStorage.setItem(sessionInitKey, '1'); } catch (e) {}
                } else if (Array.isArray(initialUnreadItems) && initialUnreadItems.length > 0) {
                    enqueueToastNotifications(initialUnreadItems);
                }

                // Al cerrar sesión, detener sondeo y limpiar estado de sesión
                document.querySelectorAll('form[action*="logout"]').forEach(function(logoutForm) {
                    logoutForm.addEventListener('submit', function() {
                        if (pollTimerId) clearInterval(pollTimerId);
                        try {
                            sessionStorage.removeItem(sessionInitKey);
                            sessionStorage.removeItem(seenIdsKey);
                        } catch (e) {}
                    });
                });
            });

            async function checkLiveNotifications() {
                try {
                    const response = await fetch(pollUrl, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-Background-Polling': 'true'
                        }
                    });

                    // Si el servidor indica sesión expirada por inactividad, detener el sondeo inmediatamente
                    if (response.status === 401 || response.status === 419) {
                        if (pollTimerId) clearInterval(pollTimerId);
                        if (typeof window.showExpiredSessionModal === 'function') {
                            window.showExpiredSessionModal();
                        } else {
                            window.location.href = "{{ route('login') }}?expired=1";
                        }
                        return;
                    }

                    if (!response.ok) return;
                    const data = await response.json();

                    if (data && data.session_expired) {
                        if (pollTimerId) clearInterval(pollTimerId);
                        if (typeof window.showExpiredSessionModal === 'function') {
                            window.showExpiredSessionModal();
                        } else {
                            window.location.href = "{{ route('login') }}?expired=1";
                        }
                        return;
                    }

                    // Actualizar contadores y lista desplegable de la campana
                    updateCounters(data.unread_count ?? 0);

                    if (Array.isArray(data.dropdown_items)) {
                        renderDropdownItems(data.dropdown_items);
                    }

                    // Encolar cualquier nueva notificación no leída recibida durante la sesión activa
                    if (Array.isArray(data.unread_items) && data.unread_items.length > 0) {
                        enqueueToastNotifications(data.unread_items);
                    } else if (data.latest && data.latest.id) {
                        enqueueToastNotifications([data.latest]);
                    }
                } catch (e) {
                    // Silencio intencional ante microcortes de red
                }
            }

            // Iniciar sondeo recurrente cada 15s
            pollTimerId = setInterval(checkLiveNotifications, pollIntervalMs);
            @endauth
        })();
    </script>
</body>
</html>
