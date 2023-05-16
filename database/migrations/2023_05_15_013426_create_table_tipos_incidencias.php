<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTableTiposIncidencias extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tipos_incidencias', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 50);
            $table->text('descripcion')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        $this::insertDataPredeterminada();
    }

    public static function insertDataPredeterminada() {
        DB::table('tipos_incidencias')->insert(
            [
                'nombre'=>'Falla electrica',
                'descripcion'=>'Fallas electricas de la instalación', 
                'created_at'=>now(), 
                'updated_at'=>now()
            ]
        );

        DB::table('tipos_incidencias')->insert(
            [
                'nombre'=>'Equipo Dañado',
                'descripcion'=>'El equipo de encuentra dañado y no es utlizable', 
                'created_at'=>now(), 
                'updated_at'=>now()
            ]
        );

        DB::table('tipos_incidencias')->insert(
            [
                'nombre'=>'sin Internet',
                'descripcion'=>'Si conexion disponible a internet', 
                'created_at'=>now(), 
                'updated_at'=>now()
            ]
        );

        DB::table('tipos_incidencias')->insert(
            [
                'nombre'=>'Otros',
                'descripcion'=>'Ulizar cuando no aplique ninguno tipo de incidencia', 
                'created_at'=>now(), 
                'updated_at'=>now()
            ]
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tipos_incidencias');
    }
}
