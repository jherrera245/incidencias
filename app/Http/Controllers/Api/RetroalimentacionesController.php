<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Retroalimentaciones;
use Illuminate\Http\Request;
use DB;
use Validator;

class RetroalimentacionesController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        if ($request) {
            if ($user->is_admin) {
                //consultamos la tabla
                $query = trim($request->get('searchText'));
                $retroalimentaciones =$this->listarTodos($query);

                $response = [
                    "retroalimentaciones" => $retroalimentaciones,
                    "searhText"=>$query
                ];

                return response()->json($response);
            }
            else {
                //consultamos la tabla
                $query = trim($request->get('searchText'));
                $retroalimentaciones =$this->listarPorUsuario($query);

                $response = [
                    "retroalimentaciones" => $retroalimentaciones,
                    "searhText"=>$query
                ];
                return response()->json($response);
            }
        }
    }

    private function listarTodos($query = null){
        $retroalimentaciones = DB::table('retroalimentaciones AS re')
        ->join('incidencias AS in', 're.id_incidencia', '=', 'in.id')
        ->join('users AS u', 're.id_usuario_resolucion', '=', 'u.id')
        ->join('empleados as emp', 'u.id_empleado', '=', 'emp.id')
        ->join('cargos as car', 'emp.id_cargo','=','car.id')
        ->join('departamentos as dep', 'emp.id_departamento','=','dep.id')
        ->select(
            're.id', 'in.descripcion AS descripcionIncidencia', 're.descripcion AS retroalimentacion', 'emp.nombres', 'emp.apellidos', 
            'car.nombre AS cargo', 'dep.nombre AS departamento', 're.status', 're.created_at AS fecha'
        )
        ->where(function($groupQuery) use ($query){
            $groupQuery->where('emp.nombres','LIKE', '%'.$query.'%')
            ->orwhere('emp.apellidos', 'LIKE', '%'.$query.'%')
            ->orwhere('car.nombre','LIKE', '%'.$query.'%')
            ->orwhere('dep.nombre','LIKE', '%'.$query.'%');
        })
        ->where('re.status','=','1')
        ->orderBy('re.id','desc')
        ->paginate(7);

        return $retroalimentaciones;
    }

    private function listarPorUsuario($query = null){
        $user = auth()->user();
        $retroalimentaciones = DB::table('retroalimentaciones AS re')
        ->join('incidencias AS in', 're.id_incidencia', '=', 'in.id')
        ->join('users AS u', 're.id_usuario_resolucion', '=', 'u.id')
        ->join('empleados as emp', 'u.id_empleado', '=', 'emp.id')
        ->join('cargos as car', 'emp.id_cargo','=','car.id')
        ->join('departamentos as dep', 'emp.id_departamento','=','dep.id')
        ->select(
            're.id', 'in.descripcion AS descripcionIncidencia', 're.descripcion AS retroalimentacion', 'emp.nombres', 'emp.apellidos', 
            'car.nombre AS cargo', 'dep.nombre AS departamento', 're.status', 're.created_at AS fecha'     
        )
        ->where(function($groupQuery) use ($query){
            $groupQuery->where('emp.nombres','LIKE', '%'.$query.'%')
            ->orwhere('emp.apellidos', 'LIKE', '%'.$query.'%')
            ->orwhere('car.nombre','LIKE', '%'.$query.'%')
            ->orwhere('dep.nombre','LIKE', '%'.$query.'%');
        })
        ->where('in.id_usuario','=',$user->id_empleado)
        ->where('re.status','=','1')
        ->orderBy('re.id','desc')
        ->paginate(7);

        return $retroalimentaciones;
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        $inputs = $request->all();

        //validacion de entradas
        $validator = Validator::make($inputs, [
            'id_incidencia'=>'required',
            'descripcion'=>'required'
        ]);

        if ($validator->fails()) {
            $response = [
                "status"=> false,
                "errors"=>$validator->errors()
            ];

            return response()->json($response);
        }

        $retroalimentaciones = new Retroalimentaciones();
        $retroalimentaciones->id_incidencia = $request->get('id_incidencia');
        $retroalimentaciones->id_usuario_resolucion = $user->id;
        $retroalimentaciones->descripcion = $request->get('descripcion');

        $retroalimentaciones->save();

        $response = [
            "status"=>true,
            "message"=>"Datos insertados correctamente",
            'data' => $retroalimentaciones
        ];

        return response()->json($response);
    }

    public function update(Request $request, $id)
    {
        $user = auth()->user();
        $inputs = $request->all();

        //validacion de entradas
        $validator = Validator::make($inputs, [
            'id_incidencia'=>'required',
            'descripcion'=>'required'
        ]);

        if ($validator->fails()) {
            $response = [
                "status"=> false,
                "errors"=>$validator->errors()
            ];

            return response()->json($response);
        }
        
        $retroalimentaciones = Retroalimentaciones::find($id);
        $retroalimentaciones->id_incidencia = $request->get('id_incidencia');
        $retroalimentaciones->id_usuario_resolucion = $user->id;
        $retroalimentaciones->descripcion = $request->get('descripcion');

        
        $retroalimentaciones->update();

        $response = [
            "status"=>true,
            "message"=>"Datos actualizados correctamente",
            'data' => $retroalimentaciones
        ];

        return response()->json($response);
    }

    public function destroy($id)
    {
        $retroalimentaciones = Retroalimentaciones::find($id);
        $retroalimentaciones->status = false;

        $retroalimentaciones->update();

        $response = [
            "status"=> true,
            "data"=>$retroalimentaciones,
            "message"=>"El registro fue eliminado correctamente"
        ];

        return response()->json($response);
    }

}
