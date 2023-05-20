<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use DB;
use Hash;
class UsersController extends Controller
{
    /**
     * Metodo para cargar datos de los usuarios
     * Verbo HTTP GET
    */
    public function index(Request $request)
    {
        $user = auth()->user();

        if ($request) {
            if ($user->is_admin) {
                //consultamos la tabla
                $query = trim($request->get('searchText'));
                $users =$this->listarUsuarios($query);

                $response = [
                    "users" => $users,
                    "searhText"=>$query
                ];

                return response()->json($response);
            }
        }
    }

    public function listarUsuarios($query)
    {
        $usuarios = DB::table('users as u')
        ->select('u.id', 'u.name','u.email', 'u.is_admin')
        ->where('u.name','LIKE', '%'.$query.'%')
        ->where('u.status','=','1')
        ->orderBy('u.id','desc')
        ->paginate(7);

        return $usuarios;
    }
}
