<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Modelo Eloquent institucional: Encuesta.
 *
 * Representa y gestiona la persistencia de datos y reglas de negocio asociadas a la entidad.
 */
class Encuesta extends Model
{
    use HasFactory;

    protected $table = 'encuestas';
    protected $primaryKey = 'encuesta_id';

    public const ESTADO_BORRADOR = 'BORRADOR';
    public const ESTADO_PUBLICADA = 'PUBLICADA';
    public const ESTADO_CERRADA = 'CERRADA';

    public const TIPO_GENERAL = 'GENERAL';
    public const TIPO_POST_CITA = 'POST_CITA';

    protected $fillable = [
        'titulo',
        'slug',
        'descripcion',
        'objetivo',
        'estado',
        'tipo',
        'disponible_desde',
        'disponible_hasta',
        'es_anonima',
        'permite_multiples_respuestas',
        'creado_por',
    ];    /**
     * Define los casteos de tipos nativos para los atributos del modelo Encuesta.
     *
     * @return array<string, string>
     */
protected function casts(): array
    {
        return [
            'disponible_desde' => 'date',
            'disponible_hasta' => 'date',
            'es_anonima' => 'boolean',
            'permite_multiples_respuestas' => 'boolean',
        ];
    }    /**
     * Inicializa eventos y escuchadores del ciclo de vida del modelo Encuesta.
     *
     * @return void
     */
public static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->slug)) {
                $slug = Str::slug($model->titulo);
                $original = $slug;
                $count = 1;
                while (static::where('slug', $slug)->exists()) {
                    $slug = "{$original}-" . $count++;
                }
                $model->slug = $slug;
            }
        });
    }    /**
     * Relación de uno a muchos hacia la colección asociada.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
public function preguntas(): HasMany
    {
        return $this->hasMany(EncuestaPregunta::class, 'encuesta_id', 'encuesta_id')->orderBy('orden');
    }    /**
     * Relación de pertenencia hacia la entidad correspondiente.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
public function respuestas(): HasMany
    {
        return $this->hasMany(EncuestaRespuesta::class, 'encuesta_id', 'encuesta_id');
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
        return $query->where('estado', self::ESTADO_PUBLICADA);
    }    /**
     * Scope local para filtrar registros por vigentes.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query Consulta base de Eloquent.
     * @return \Illuminate\Database\Eloquent\Builder
     */
public function scopeVigentes($query)
    {
        $today = now()->toDateString();
        return $query->where('estado', self::ESTADO_PUBLICADA)
            ->where(function ($q) use ($today) {
                $q->whereNull('disponible_desde')->orWhere('disponible_desde', '<=', $today);
            })
            ->where(function ($q) use ($today) {
                $q->whereNull('disponible_hasta')->orWhere('disponible_hasta', '>=', $today);
            });
    }    /**
     * Método auxiliar de entidad 'estaDisponible'.
     *
     * @return bool
     */
public function estaDisponible(): bool
    {
        if ($this->estado !== self::ESTADO_PUBLICADA) {
            return false;
        }

        $today = now()->toDateString();
        if ($this->disponible_desde && $this->disponible_desde->toDateString() > $today) {
            return false;
        }
        if ($this->disponible_hasta && $this->disponible_hasta->toDateString() < $today) {
            return false;
        }

        return true;
    }    /**
     * Método auxiliar de entidad 'haRespondido'.
     *
     * @return bool
     */
public function haRespondido(?User $user, ?int $citaId = null): bool
    {
        if (!$user) {
            return false;
        }

        if ($this->tipo === 'POST_CITA' && $citaId) {
            return $this->respuestas()->where('cita_id', $citaId)->exists();
        }

        if ($this->permite_multiples_respuestas) {
            return false;
        }

        $userHash = hash('sha256', $user->usuario_id . '|' . $this->encuesta_id . '|' . config('app.key'));

        return $this->respuestas()
            ->where(function ($q) use ($user, $userHash) {
                $q->where('usuario_id', $user->usuario_id)
                  ->orWhere('ip_hash', $userHash);
            })
            ->exists();
    }    /**
     * Descriptor para el atributo calculado 'estadoBadge'.
     *
     * @return mixed
     */
public function getEstadoBadgeAttribute(): string
    {
        return match ($this->estado) {
            self::ESTADO_PUBLICADA => 'bg-success',
            self::ESTADO_BORRADOR => 'bg-secondary',
            self::ESTADO_CERRADA => 'bg-danger',
            default => 'bg-secondary',
        };
    }
}

