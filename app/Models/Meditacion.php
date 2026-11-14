<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Modelo Eloquent institucional: Meditacion.
 *
 * Representa y gestiona la persistencia de datos y reglas de negocio asociadas a la entidad.
 */
class Meditacion extends Model
{
    use HasFactory;

    protected $table = 'meditaciones';
    protected $primaryKey = 'meditacion_id';

    public const CATEGORIA_RESPIRACION = 'RESPIRACION';
    public const CATEGORIA_RELAJACION_MUSCULAR = 'RELAJACION_MUSCULAR';
    public const CATEGORIA_MINDFULNESS = 'MINDFULNESS';
    public const CATEGORIA_PAUSA_ESTUDIO = 'PAUSA_ESTUDIO';
    public const CATEGORIA_DESCANSO = 'DESCANSO';

    public const NIVEL_PRINCIPIANTE = 'PRINCIPIANTE';
    public const NIVEL_INTERMEDIO = 'INTERMEDIO';
    public const NIVEL_AVANZADO = 'AVANZADO';

    public const TIPO_INTERACTIVO = 'INTERACTIVO';
    public const TIPO_AUDIO = 'AUDIO';
    public const TIPO_VIDEO = 'VIDEO';
    public const TIPO_GUIA_TEXTUAL = 'GUIA_TEXTUAL';

    protected $fillable = [
        'titulo',
        'slug',
        'categoria',
        'descripcion',
        'instrucciones',
        'duracion_minutos',
        'nivel',
        'icono',
        'es_publicado',
        'vistas_count',
        'creado_por',
    ];    /**
     * Define los casteos de tipos nativos para los atributos del modelo Meditacion.
     *
     * @return array<string, string>
     */
protected function casts(): array
    {
        return [
            'instrucciones' => 'array',
            'duracion_minutos' => 'integer',
            'es_publicado' => 'boolean',
            'vistas_count' => 'integer',
        ];
    }    /**
     * Inicializa eventos y escuchadores del ciclo de vida del modelo Meditacion.
     *
     * @return void
     */
public static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->slug)) {
                $slug = Str::slug($model->titulo);
                $originalSlug = $slug;
                $count = 1;
                while (static::where('slug', $slug)->exists()) {
                    $slug = "{$originalSlug}-" . $count++;
                }
                $model->slug = $slug;
            }
        });
    }    /**
     * Relación de pertenencia hacia la entidad correspondiente.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
public function sesiones(): HasMany
    {
        return $this->hasMany(MeditacionSesion::class, 'meditacion_id', 'meditacion_id');
    }    /**
     * Relación de pertenencia hacia la entidad correspondiente.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por', 'usuario_id');
    }    /**
     * Scope local para filtrar registros por publicadas.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query Consulta base de Eloquent.
     * @return \Illuminate\Database\Eloquent\Builder
     */
public function scopePublicadas($query)
    {
        return $query->where('es_publicado', true);
    }    /**
     * Scope local para filtrar registros por porCategoria.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query Consulta base de Eloquent.
     * @return \Illuminate\Database\Eloquent\Builder
     */
public function scopePorCategoria($query, ?string $categoria)
    {
        if ($categoria && in_array($categoria, array_keys(self::categoriasCatalogo()))) {
            return $query->where('categoria', $categoria);
        }
        return $query;
    }    /**
     * Método auxiliar de entidad 'categoriasCatalogo'.
     *
     * @return array
     */
public static function categoriasCatalogo(): array
    {
        return [
            self::CATEGORIA_RESPIRACION => [
                'label' => 'Respiración Consciente',
                'badge' => 'bg-info text-dark',
                'icon' => 'bi-lungs',
                'color' => '#0dcaf0',
            ],
            self::CATEGORIA_RELAJACION_MUSCULAR => [
                'label' => 'Relajación Muscular',
                'badge' => 'bg-success',
                'icon' => 'bi-heart-pulse',
                'color' => '#198754',
            ],
            self::CATEGORIA_MINDFULNESS => [
                'label' => 'Atención Plena (Mindfulness)',
                'badge' => 'bg-primary',
                'icon' => 'bi-eye',
                'color' => '#0d6efd',
            ],
            self::CATEGORIA_PAUSA_ESTUDIO => [
                'label' => 'Pausas Activas de Estudio',
                'badge' => 'bg-warning text-dark',
                'icon' => 'bi-book-half',
                'color' => '#ffc107',
            ],
            self::CATEGORIA_DESCANSO => [
                'label' => 'Preparación para el Descanso',
                'badge' => 'bg-indigo text-white',
                'icon' => 'bi-moon-stars',
                'color' => '#4C1D95',
            ],
        ];
    }    /**
     * Descriptor para el atributo calculado 'categoriaLabel'.
     *
     * @return mixed
     */
public function getCategoriaLabelAttribute(): string
    {
        $cats = self::categoriasCatalogo();
        return $cats[$this->categoria]['label'] ?? $this->categoria;
    }    /**
     * Descriptor para el atributo calculado 'categoriaBadge'.
     *
     * @return mixed
     */
public function getCategoriaBadgeAttribute(): string
    {
        $cats = self::categoriasCatalogo();
        return $cats[$this->categoria]['badge'] ?? 'bg-secondary';
    }    /**
     * Descriptor para el atributo calculado 'categoriaIcon'.
     *
     * @return mixed
     */
public function getCategoriaIconAttribute(): string
    {
        $cats = self::categoriasCatalogo();
        return $cats[$this->categoria]['icon'] ?? 'bi-activity';
    }    /**
     * Descriptor para el atributo calculado 'nivelLabel'.
     *
     * @return mixed
     */
public function getNivelLabelAttribute(): string
    {
        return match ($this->nivel) {
            self::NIVEL_PRINCIPIANTE => 'Principiante',
            self::NIVEL_INTERMEDIO => 'Intermedio',
            self::NIVEL_AVANZADO => 'Avanzado',
            default => 'Todos los niveles',
        };
    }    /**
     * Método auxiliar de entidad 'tiposContenidoCatalogo'.
     *
     * @return array
     */
public static function tiposContenidoCatalogo(): array
    {
        return [
            self::TIPO_INTERACTIVO => [
                'label' => 'Ejercicio Interactivo (Animación Guiada)',
                'badge' => 'bg-info-subtle text-info-emphasis border border-info-subtle',
                'icon' => 'bi-activity',
            ],
            self::TIPO_AUDIO => [
                'label' => 'Audio Guiado / Podcast',
                'badge' => 'bg-purple-subtle text-purple-emphasis border border-purple-subtle',
                'icon' => 'bi-headphones',
            ],
            self::TIPO_VIDEO => [
                'label' => 'Video Guiado (YouTube / Vimeo / MP4)',
                'badge' => 'bg-danger-subtle text-danger-emphasis border border-danger-subtle',
                'icon' => 'bi-play-circle',
            ],
            self::TIPO_GUIA_TEXTUAL => [
                'label' => 'Guía Textual de Lectura Estructurada',
                'badge' => 'bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle',
                'icon' => 'bi-file-text',
            ],
        ];
    }    /**
     * Descriptor para el atributo calculado 'tipoContenido'.
     *
     * @return mixed
     */
public function getTipoContenidoAttribute(): string
    {
        if (is_array($this->instrucciones) && isset($this->instrucciones['tipo_contenido'])) {
            return $this->instrucciones['tipo_contenido'];
        }
        return self::TIPO_INTERACTIVO;
    }    /**
     * Descriptor para el atributo calculado 'tipoContenidoLabel'.
     *
     * @return mixed
     */
public function getTipoContenidoLabelAttribute(): string
    {
        $tipos = self::tiposContenidoCatalogo();
        return $tipos[$this->tipo_contenido]['label'] ?? 'Interactivo';
    }    /**
     * Descriptor para el atributo calculado 'tipoContenidoBadge'.
     *
     * @return mixed
     */
public function getTipoContenidoBadgeAttribute(): string
    {
        $tipos = self::tiposContenidoCatalogo();
        return $tipos[$this->tipo_contenido]['badge'] ?? 'bg-secondary';
    }    /**
     * Descriptor para el atributo calculado 'tipoContenidoIcon'.
     *
     * @return mixed
     */
public function getTipoContenidoIconAttribute(): string
    {
        $tipos = self::tiposContenidoCatalogo();
        return $tipos[$this->tipo_contenido]['icon'] ?? 'bi-activity';
    }    /**
     * Descriptor para el atributo calculado 'mediaUrl'.
     *
     * @return mixed
     */
public function getMediaUrlAttribute(): ?string
    {
        if (is_array($this->instrucciones) && isset($this->instrucciones['media_url'])) {
            return $this->instrucciones['media_url'];
        }
        return null;
    }    /**
     * Descriptor para el atributo calculado 'autor'.
     *
     * @return mixed
     */
public function getAutorAttribute(): ?string
    {
        if (is_array($this->instrucciones) && isset($this->instrucciones['autor'])) {
            return $this->instrucciones['autor'];
        }
        return null;
    }    /**
     * Descriptor para el atributo calculado 'puntos'.
     *
     * @return mixed
     */
public function getPuntosAttribute(): int
    {
        if (is_array($this->instrucciones) && isset($this->instrucciones['puntos'])) {
            return (int) $this->instrucciones['puntos'];
        }
        return 2;
    }    /**
     * Descriptor para el atributo calculado 'transcripcion'.
     *
     * @return mixed
     */
public function getTranscripcionAttribute(): ?string
    {
        if (is_array($this->instrucciones) && isset($this->instrucciones['transcripcion'])) {
            return $this->instrucciones['transcripcion'];
        }
        return null;
    }    /**
     * Descriptor para el atributo calculado 'pasos'.
     *
     * @return mixed
     */
public function getPasosAttribute(): array
    {
        if (!is_array($this->instrucciones)) {
            return [];
        }
        if (isset($this->instrucciones['pasos']) && is_array($this->instrucciones['pasos'])) {
            return $this->instrucciones['pasos'];
        }
        if (isset($this->instrucciones[0])) {
            return $this->instrucciones;
        }
        return [];
    }

    /**
     * Extrae el ID único de 11 caracteres de un enlace de YouTube soportando todas las variantes URL institucionales.
     */
    public static function extractYoutubeId(?string $url): ?string
    {
        if (empty($url)) {
            return null;
        }

        $pattern = '%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?|live|shorts)/|.*[?&]v=)|youtu\.be/)([^"&?/\s]{11})%i';
        if (preg_match($pattern, trim($url), $matches)) {
            return $matches[1];
        }

        return null;
    }    /**
     * Descriptor para el atributo calculado 'youtubeVideoId'.
     *
     * @return mixed
     */
public function getYoutubeVideoIdAttribute(): ?string
    {
        return self::extractYoutubeId($this->media_url);
    }    /**
     * Descriptor para el atributo calculado 'embedVideoUrl'.
     *
     * @return mixed
     */
public function getEmbedVideoUrlAttribute(): ?string
    {
        $url = $this->media_url;
        if (!$url) return null;

        // YouTube con privacidad mejorada (youtube-nocookie)
        $ytId = self::extractYoutubeId($url);
        if ($ytId) {
            return 'https://www.youtube-nocookie.com/embed/' . $ytId;
        }

        // Vimeo
        if (preg_match('/vimeo\.com\/(?:channels\/(?:\w+\/)?|groups\/(?:[^\/]*)\/videos\/|album\/(?:\d+)\/video\/|video\/|)(\d+)/i', $url, $matches)) {
            return 'https://player.vimeo.com/video/' . $matches[1];
        }

        return $url;
    }
}

