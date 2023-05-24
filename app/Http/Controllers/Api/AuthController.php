<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\TokensUsers;
use Validator;

class AuthController extends Controller
{

    //validaciones para entradas de datos
    private function validatorInputs($inputs) {
        $validator = Validator::make($inputs, [
            'email'=>'required',
            'password'=>'required',
        ]);

        return $validator;
    }

    //guadar token de firebase
    private function saveTokenFirebase(Request $request, $userId) {
        if ($request->get('firebase_token')) {
            $token = $request->get('firebase_token');

            $getTokens = TokensUsers::where("token", $token)->count();

            if ($getTokens == 0) {
                $tokenUser = new TokensUsers();
                $tokenUser->id_usuario = $userId;
                $tokenUser->token = $token;
                $tokenUser->save();
            }
        }
    }

    /**
     * Metodo para logearse en el sistema
     */
    public function login(Request $request){

        $inputs = $request->all();

        //validacion de entradas
        $validator = $this->validatorInputs($inputs);

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

        //si el usuario tiene status 0 se anula el incio de sesion
        if($user->status == 0) {
            $response = [
                'status'=>false,
                'message' => 'Acceso no autorizado!!'
            ];
            return response()->json($response);
        }

        $token = $user->createToken('auth_token')->plainTextToken;
        //guarda el token del usuario en la base de datos
        $this->saveTokenFirebase($request, $user->id);

        $response = [
            "status"=>true,
            'message' => 'Bienvenido '.$user->name.', iniciaste sesión correctamente!!',
            'name' =>$user->name,
            'email'=>$user->email,
            'rol'=>$user->is_admin,
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

        $response = [
            'message' => 'Su sesión fue finalizada correctamente!!'
        ];

        return response()->json($response);
    }
}
