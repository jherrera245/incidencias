<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Incidencias extends Model
{
    use HasFactory;

    //tabla
    protected $table = 'incidencias';

    //columnas
    protected $fillable = [
        'id',
        'id_tipo_incidencia',
        'descripcion',
        'id_usuario',
        'imagen',
        'status_resolucion',
        'status',
    ];
}
