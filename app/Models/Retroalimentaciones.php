<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Retroalimentaciones extends Model
{
    use HasFactory;

    protected $table = 'incidencias';

    //columnas
    protected $fillable = [
        'id',
        'id_incidencia',
        'id_usuario_resolucion',
        'descripcion',
        'fecha_resolucion',
        'hora_resolucion',
        'status',
    ];
}
