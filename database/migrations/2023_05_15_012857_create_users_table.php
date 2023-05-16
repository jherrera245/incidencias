<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->foreignId('id_empleado');
            $table->foreign('id_empleado')->references('id')->on('empleados');
            $table->boolean('is_admin')->default(false);
            $table->boolean('status')->default(true);
            $table->rememberToken();
            $table->timestamps();
        });

        $this::insertDataPredeterminada();
    }

    public function insertDataPredeterminada() {
        DB::table('users')->insert([
            "name" =>"Josue Herrera",
            "email"=>"herrera_jh17@outlook.com",
            "password"=>Hash::make("245jh17@"),
            "id_empleado"=>1,
            "is_admin"=>true,
            "created_at"=>now(), 
            "updated_at"=>now()
        ]);

        DB::table('users')->insert([
            "name" =>"Cesar Maurico",
            "email"=>"mauricio@gmail.com",
            "password"=>Hash::make("cesar123@"),
            "id_empleado"=>2,
            "is_admin"=>false,
            "created_at"=>now(), 
            "updated_at"=>now()
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
