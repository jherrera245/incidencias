<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTableEmpleados extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('empleados', function (Blueprint $table) {
            $table->id();
            $table->string("nombres", 100);
            $table->string("apellidos", 100);
            $table->string("dui", 10);
            $table->foreignId('id_departamento');
            $table->foreign('id_departamento')->references('id')->on('departamentos');
            $table->foreignId('id_cargo');
            $table->foreign('id_cargo')->references('id')->on('cargos');
            $table->string("telefono", 15)->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        //default
        $this::insertDataPredeterminada();
    }

    public function insertDataPredeterminada() {
        DB::table('empleados')->insert(
            [
                "nombres"=>"Josue  Isai",
                "apellidos"=>"Herrera Benitez",
                "dui" => "05998731-4",
                "id_departamento"=> 1,
                "id_cargo"=>1,
                "telefono"=>"7728-8054",
                "created_at"=>now(), 
                "updated_at"=>now()
            ]
        );

        DB::table('empleados')->insert(
            [
                "nombres"=>"Cesar Mauricio",
                "apellidos"=>"Martinez Reyes",
                "dui" => "00000000-0",
                "id_departamento"=> 1,
                "id_cargo"=>1,
                "telefono"=>"6728-9292",
                "created_at"=>now(), 
                "updated_at"=>now()
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
        Schema::dropIfExists('empleados');
    }
}
