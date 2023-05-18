<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Validator;

class AuthController extends Controller
{
    /**
     * Metodo para logearse en el sistema
     */
    public function login(Request $request){

        $inputs = $request->all();

        //validacion de entradas
        $validator = Validator::make($inputs, [
            'email'=>'required',
            'password'=>'required',
        ]);

        if ($validator->fails()) {
            $response = [
                "status"=> false,
                "errors"=>$validator->errors()
            ];
            return response()->json($response);
        }

        if (!Auth::attempt($request->only('email', 'password')))
        {
            $response = [
                'status'=>false,
                'message' => 'Acceso no autorizado!!'
            ];
            return response()->json($response);
        }

        $user = User::where('email', $request['email'])->firstOrFail();

        $token = $user->createToken('auth_token')->plainTextToken;

        $response = [
            "status"=>true,
            'message' => 'Bienvenido '.$user->name.', iniciaste sesión correctamente!!',
            'access_token' => $token, 
            'token_type' => 'Bearer', 
        ];

        return response()->json($response);
    }


    /**
     * Metodo para cerrar sesion y borrar token
     */
    public function logout()
    {
        auth()->user()->tokens()->delete();

        return [
            'message' => 'Su sessión fue finalizada correctamente!!'
        ];
    }
}
