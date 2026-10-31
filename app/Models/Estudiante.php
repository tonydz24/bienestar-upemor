<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Estudiante extends Model
{
    use HasFactory;

    protected $table = 'estudiantes';
    protected $primaryKey = 'usuario_id';
    public $incrementing = false;

    protected $fillable = [
        'usuario_id',
        'programa_id',
        'matricula',
        'cuatrimestre',
        'grupo',
        'fecha_ingreso',
        'saldo_puntos',
        'permite_visualizacion',
    ];

    protected function casts(): array
    {
        return [
            'fecha_ingreso' => 'date',
            'cuatrimestre' => 'integer',
            'saldo_puntos' => 'integer',
            'permite_visualizacion' => 'boolean',
        ];
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id', 'usuario_id');
    }

    public function programa()
    {
        return $this->belongsTo(ProgramaEducativo::class, 'programa_id', 'programa_id');
    }
}
