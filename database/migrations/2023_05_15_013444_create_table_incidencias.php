<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTableIncidencias extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('incidencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_tipo_incidencia');
            $table->foreign('id_tipo_incidencia')->references('id')->on('tipos_incidencias');
            $table->text('descripcion');
            $table->foreignId('id_usuario');
            $table->foreign('id_usuario')->references('id')->on('users');
            $table->string('imagen', 200);
            $table->boolean('status_resolucion')->default(false);
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
        Schema::dropIfExists('incidencias');
    }
}
