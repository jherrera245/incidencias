<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTableRetroalimentaciones extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('retroalimentaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_incidencia');
            $table->foreign('id_incidencia')->references('id')->on('incidencias');
            $table->foreignId('id_usuario_resolucion');
            $table->foreign('id_usuario_resolucion')->references('id')->on('users');
            $table->text('descripcion');
            $table->date('fecha_resolucion');
            $table->time('hora_resolucion');
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('retroalimentaciones');
    }
}
