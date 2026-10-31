<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProgramaEducativo extends Model
{
    use HasFactory;

    protected $table = 'programas_educativos';
    protected $primaryKey = 'programa_id';

    protected $fillable = [
        'clave',
        'nombre',
        'descripcion',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    public function estudiantes()
    {
        return $this->hasMany(Estudiante::class, 'programa_id', 'programa_id');
    }
}
