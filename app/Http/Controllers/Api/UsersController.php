<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use DB;
use Hash;
use Validator;
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
        ->join('empleados as emp', 'u.id_empleado', '=', 'emp.id')
        ->select('u.id', 'u.name','u.email', 'emp.id AS idEmpleado', 'emp.nombres', 'emp.apellidos', 'u.is_admin')
        ->where('u.name','LIKE', '%'.$query.'%')
        ->where(function($groupQuery) use ($query){
            $groupQuery->where('emp.nombres','LIKE', '%'.$query.'%')
            ->orwhere('emp.apellidos', 'LIKE', '%'.$query.'%')
            ->orwhere('u.name','LIKE', '%'.$query.'%')
            ->orwhere('u.email', 'LIKE', '%'.$query.'%');
        })
        ->where('u.status','=','1')
        ->orderBy('u.id','desc')
        ->paginate(7);

        return $usuarios;
    }

    /**
     * Metodo para obtener la empleados de los tipos de incidencias
    */
    public function listaEmpleados(){
        $empleados = DB::table('empleados as emp')
        ->select('emp.id', 'emp.nombres', 'emp.apellidos')
        ->where('emp.status', '=', '1')
        ->get();

        $response = [
            "status"=>true,
            "empleados"=>$empleados
        ];

        return response()->json($response);
    }

    //validaciones para entradas de datos
    private function validatorInputs($inputs) {
        $validator = Validator::make($inputs, [
            'id_empleado' => 'required',
            'name'=>'required',
            'email'=>'required',
            'password'=>'required'
        ]);

        return $validator;
    }

    public function store(Request $request)
    {
        $inputs = $request->all();

        $validator = $this->validatorInputs($inputs);

        if ($validator->fails()) {
            $response = [
                "status"=> false,
                "errors"=>$validator->errors()
            ];

            return response()->json($response);
        }
        $usuario = new User();
        $usuario->id_empleado=$request->get('id_empleado');
        $usuario->name=$request->get('name');
        $usuario->email=$request->get('email');
        $usuario->password=Hash::make($request->get('password'));
        $usuario->is_admin=($request->get('admin') == 1) ? 1 : 0;

        $usuario->save();

        $response = [
            "status"=>true,
            "message"=>"Datos insertados correctamente",
            'data' => $usuario
        ];
        return response()->json($response);
    }

    /**
     * Metodo para obtener un registro por id
     */
    public function getById(Request $request) {
        if ($request->get('id')) {
            $id = $request->get('id');
            $user = Users::find($id);

            return response()->json($user);
        }
        
        return response()->json([
            "error" => "No se encontro el registro"
        ]);
    }


    public function update(Request $request, $id)
    {
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

        $user = User::find($id);
        $user->id_empleado=$request->get('id_empleado');
        $user->name=$request->get('name');
        $user->email=$request->get('email');
        $user->password=Hash::make($request->get('password'));
        $user->is_admin=($request->get('admin') == 1) ? 1 : 0;
        
        $user->update();

        $response = [
            "status"=>true,
            "message"=>"Datos actualizados correctamente",
            'data' => $user
        ];

        return response()->json($response);
    }

    public function destroy($id)
    {
        $user = User::find($id);
        $user->status = false;
        $user->update();

        $response = [
            "status"=> true,
            "data"=>$user,
            "message"=>"El registro fue eliminado correctamente"
        ];

        return response()->json($response);
    }

}
