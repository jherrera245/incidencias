<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TokensUsers extends Model
{
    use HasFactory;

    //tabla
    protected $table = 'tokens_users';

    //columnas
    protected $fillable = [
        'id',
        'id_usuario',
        'token',
        'status',
    ];
}
