<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    /**
     * Muestra la bandeja de notificaciones institucionales del usuario.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $filtro = $request->query('filtro', 'todas');

        $query = ($filtro === 'no_leidas')
            ? $user->unreadNotifications()->orderByDesc('created_at')->orderByDesc('id')
            : $user->notifications()->orderByDesc('created_at')->orderByDesc('id');

        $notificaciones = $query->paginate(15)->withQueryString();
        $totalNoLeidas = $user->unreadNotifications()->count();
        $totalNotificaciones = $user->notifications()->count();

        return view('notificaciones.index', [
            'notificaciones' => $notificaciones,
            'filtro' => $filtro,
            'totalNoLeidas' => $totalNoLeidas,
            'totalNotificaciones' => $totalNotificaciones,
        ]);
    }

    /**
     * Marca la notificación como leída y redirige al recurso de destino institucional (FN.14).
     * Utilizado principalmente al hacer clic en un elemento de la campana de notificaciones.
     */
    public function goToResource(Request $request, string $id): RedirectResponse
    {
        $user = $request->user();
        $notificacion = $user->notifications()->where('id', $id)->firstOrFail();

        if (is_null($notificacion->read_at)) {
            $notificacion->markAsRead();
        }

        $data = $notificacion->data;
        $targetUrl = $data['url'] ?? null;

        if (is_string($targetUrl) && !empty($targetUrl)) {
            // Protección contra redirección abierta (Open Redirect)
            if (str_starts_with($targetUrl, '/')) {
                return redirect($targetUrl);
            }

            $appHost = parse_url(config('app.url'), PHP_URL_HOST);
            $targetHost = parse_url($targetUrl, PHP_URL_HOST);

            if (empty($targetHost) || $targetHost === $appHost || $targetHost === $request->getHost()) {
                return redirect($targetUrl);
            }
        }

        return redirect()->route('notificaciones.index');
    }

    /**
     * Marca una notificación individual como leída.
     * Retorna JSON para llamadas asíncronas o redirige a la lista de notificaciones.
     */
    public function markAsRead(Request $request, string $id): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        $notificacion = $user->notifications()->where('id', $id)->firstOrFail();

        if (is_null($notificacion->read_at)) {
            $notificacion->markAsRead();
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'id' => $id,
                'read_at' => $notificacion->fresh()?->read_at?->toIso8601String() ?? now()->toIso8601String(),
                'unread_count' => $user->unreadNotifications()->count(),
                'mensaje' => 'Notificación marcada como leída.',
            ]);
        }

        // Si la petición proviene de la vista de notificaciones o solicita explícitamente quedarse
        if ($request->filled('stay') || $request->filled('filtro') || str_contains($request->header('referer', ''), '/notificaciones')) {
            return redirect()->route('notificaciones.index', $request->only('filtro', 'page'))
                ->with('success', 'Notificación marcada como leída.');
        }

        // Fallback de retrocompatibilidad para llamadas directas / tests legados:
        $data = $notificacion->data;
        $targetUrl = is_array($data) ? ($data['url'] ?? $data['action_url'] ?? null) : null;
        if (is_string($targetUrl) && !empty($targetUrl)) {
            if (str_starts_with($targetUrl, '/')) {
                return redirect($targetUrl);
            }
            $appHost = parse_url(config('app.url'), PHP_URL_HOST);
            $targetHost = parse_url($targetUrl, PHP_URL_HOST);
            if (empty($targetHost) || $targetHost === $appHost || $targetHost === $request->getHost()) {
                return redirect($targetUrl);
            }
        }

        return redirect()->route('notificaciones.index', $request->only('filtro', 'page'))
            ->with('success', 'Notificación marcada como leída.');
    }

    /**
     * Marca todas las notificaciones pendientes del usuario como leídas.
     */
    public function markAllAsRead(Request $request): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        $user->unreadNotifications->markAsRead();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'unread_count' => 0,
                'mensaje' => 'Todas las notificaciones han sido marcadas como leídas.',
            ]);
        }

        return redirect()->route('notificaciones.index', $request->only('filtro', 'page'))
            ->with('success', 'Todas las notificaciones han sido marcadas como leídas.');
    }

    /**
     * Endpoint de sondeo asíncrono ligero para notificaciones no leídas en vivo (FN.6, FN.11, FN.14).
     * Retorna el conteo, la lista de no leídas recientes para la cola de toasts (5s)
     * y los elementos recientes para mantener sincronizado el menú de la campana sin marcarlos como leídos.
     */
    public function getUnreadRecent(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json([
                'unread_count' => 0,
                'latest' => null,
                'unread_items' => [],
                'dropdown_items' => [],
            ], 401);
        }

        $unreadCount = $user->unreadNotifications()->count();

        $recentUnread = $user->unreadNotifications()
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->take(10)
            ->get();

        $unreadItems = $recentUnread->map(function ($notif) {
            return $this->formatNotificationItem($notif);
        })->values()->all();

        $latestData = $unreadItems[0] ?? null;

        $recentAll = $user->notifications()
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->take(5)
            ->get();

        $dropdownItems = $recentAll->map(function ($notif) {
            return $this->formatNotificationItem($notif);
        })->values()->all();

        return response()->json([
            'unread_count' => $unreadCount,
            'latest' => $latestData,
            'unread_items' => $unreadItems,
            'dropdown_items' => $dropdownItems,
        ]);
    }

    /**
     * Formatea una notificación de base de datos con sanitización de seguridad y privacidad.
     */
    private function formatNotificationItem(object $notif): array
    {
        $data = is_array($notif->data) ? $notif->data : [];
        $tipo = (string) ($data['tipo'] ?? 'general');
        $titulo = trim(strip_tags((string) ($data['titulo'] ?? 'Nueva notificación')));
        $mensaje = trim(strip_tags((string) ($data['mensaje'] ?? '')));

        // B8: Privacidad en avisos emergentes temporales (evitar exponer datos clínicos sensibles en pantalla compartida)
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
            'is_unread' => is_null($notif->read_at),
            'read_at' => $notif->read_at?->toIso8601String(),
            'url' => route('notificaciones.ir', $notif->id),
            'leer_url' => route('notificaciones.leer', $notif->id),
            'created_at' => $notif->created_at?->diffForHumans() ?? 'Ahora',
            'created_at_iso' => $notif->created_at?->toIso8601String(),
        ];
    }
}
