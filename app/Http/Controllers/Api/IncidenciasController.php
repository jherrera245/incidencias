<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\IncidenciasFormRequest;
use App\Models\Incidencias;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use File;
use DB;
use Validator;

class IncidenciasController extends Controller
{
    /**
     * Metodo para cargar datos de las incidencias
     * Verbo HTTP GET
    */
    public function index(Request $request)
    {
        $user = auth()->user();

        if ($request) {
            if ($user->is_admin) {
                //consultamos la tabla
                $query = trim($request->get('searchText'));
                $incidencias =$this->listarTodos($query);

                $response = [
                    "incidencias" => $incidencias,
                    "searhText"=>$query
                ];

                return response()->json($response);
            }else {
                //consultamos la tabla
                $query = trim($request->get('searchText'));
                $incidencias =$this->listarPorUsuario($query);

                $response = [
                    "incidencias" => $incidencias,
                    "searhText"=>$query
                ];
                return response()->json($response);
            }
        }
    }

    /**
     * Este metodo filtra todas las incidencias
     */
    private function listarTodos($query = null){
        $incidencias = DB::table('incidencias AS in')
        ->join('tipos_incidencias AS tipo', 'in.id_tipo_incidencia', '=', 'tipo.id')
        ->join('users AS u', 'in.id_usuario', '=', 'u.id')
        ->join('empleados as emp', 'u.id_empleado', '=', 'emp.id')
        ->join('cargos as car', 'emp.id_cargo','=','car.id')
        ->join('departamentos as dep', 'emp.id_departamento','=','dep.id')
        ->select(
            'in.id', 'tipo.nombre AS tipo', 'in.descripcion', 'emp.nombres', 'emp.apellidos', 
            'car.nombre AS cargo', 'dep.nombre AS departamento', 'in.imagen',
            'in.status_resolucion AS resolucion', 'in.status', 'in.created_at AS fecha'      
        )
        ->where(function($groupQuery) use ($query){
            $groupQuery->where('emp.nombres','LIKE', '%'.$query.'%')
            ->orwhere('emp.apellidos', 'LIKE', '%'.$query.'%')
            ->orwhere('car.nombre','LIKE', '%'.$query.'%')
            ->orwhere('dep.nombre','LIKE', '%'.$query.'%');
        })
        ->where('in.status','=','1')
        ->orderBy('in.id','desc')
        ->paginate(7);

        return $incidencias;
    }

    /**
     * Este metodo fltra las incidencias por id usuario que reporto
     */
    private function listarPorUsuario($query = null){
        $user = auth()->user();
        $incidencias = DB::table('incidencias AS in')
        ->join('tipos_incidencias AS tipo', 'in.id_tipo_incidencia', '=', 'tipo.id')
        ->join('users AS u', 'in.id_usuario', '=', 'u.id')
        ->join('empleados as emp', 'u.id_empleado', '=', 'emp.id')
        ->join('cargos as car', 'emp.id_cargo','=','car.id')
        ->join('departamentos as dep', 'emp.id_departamento','=','dep.id')
        ->select(
            'in.id', 'tipo.nombre AS tipo', 'in.descripcion', 'emp.nombres', 'emp.apellidos', 
            'car.nombre AS cargo', 'dep.nombre AS departamento', 'in.imagen',
            'in.status_resolucion AS resolucion', 'in.status', 'in.created_at AS fecha'      
        )
        ->where(function($groupQuery) use ($query){
            $groupQuery->where('emp.nombres','LIKE', '%'.$query.'%')
            ->orwhere('emp.apellidos', 'LIKE', '%'.$query.'%')
            ->orwhere('car.nombre','LIKE', '%'.$query.'%')
            ->orwhere('dep.nombre','LIKE', '%'.$query.'%');
        })
        ->where('u.id','=',$user->id)
        ->where('in.status','=','1')
        ->orderBy('in.id','desc')
        ->paginate(7);

        return $incidencias;
    }

    /**
     * Metodo para registrar una incidencia en la base de datos
     * Verbo HTTP POST
    */
    public function store(Request $request)
    {
        $user = auth()->user();
        $inputs = $request->all();

        //validacion de entradas
        $validator = Validator::make($inputs, [
            'tipo'=>'required|integer',
            'descripcion'=>'required',
            'imagen'=>'required'
        ]);

        if ($validator->fails()) {
            $response = [
                "status"=> false,
                "errors"=>$validator->errors()
            ];

            return response()->json($response);
        }

        $incidencia = new Incidencias();
        $incidencia->id_tipo_incidencia = $request->get('tipo');
        $incidencia->descripcion = $request->get('descripcion');
        $incidencia->id_usuario = $user->id;

        //guadar imagen
        if ($request->hasFile('imagen')) {
            $url = $request->file('imagen');
            $nombre = str_replace(' ', '-', trim($request->get('imagen')));
            $nombre = hash('sha256', $nombre.date('Y-m-d H:i:s')); //ciframos el nombre
            $file = $nombre.".".$url->guessExtension();
            //guardamos el archivo en el servidor
            Storage::disk('incidencias')->put($file, File::get($url));
            $incidencia->imagen = $file;
        }

        $incidencia->save();

        $response = [
            "status"=>true,
            "message"=>"Datos insertados correctamente",
            'data' => $incidencia
        ];

        return response()->json($response);
    }

    /**
     * Metodo para registrar una incidencia en la base de datos
     * Verbo HTTP POST
    */
    public function update(Request $request, $id)
    {
        $user = auth()->user();
        $inputs = $request->all();

        //validacion de entradas
        $validator = Validator::make($inputs, [
            'tipo'=>'required|integer',
            'descripcion'=>'required',
            'imagen'=>'required'
        ]);

        if ($validator->fails()) {
            $response = [
                "status"=> false,
                "errors"=>$validator->errors()
            ];

            return response()->json($response);
        }

        $incidencia = Incidencias::find($id);
        $oldImage = $incidencia->imagen;
        $incidencia->id_tipo_incidencia = $request->get('tipo');
        $incidencia->descripcion = $request->get('descripcion');
        $incidencia->id_usuario = $user->id;
        
        //guadar imagen
        if ($request->hasFile('imagen')) {
            $url = $request->file('imagen');
            Storage::disk('incidencias')->delete($oldImage); //borrar imagen anterior
            $nombre = str_replace(' ', '-', trim($request->get('imagen')));
            $nombre = hash('sha256', $nombre.date('Y-m-d H:i:s')); //ciframos el nombre
            $file = $nombre.".".$url->guessExtension();
            //guardamos el archivo en el servidor
            Storage::disk('incidencias')->put($file, File::get($url));
            $incidencia->imagen = $file;
        }
        
        $incidencia->update();

        $response = [
            "status"=>true,
            "message"=>"Datos actualizados correctamente",
            'data' => $incidencia
        ];

        return response()->json($response);
    }

    /**
     * Metodo para eiminar una incidencia
     * VERBO HTTP
     */
    public function destroy($id)
    {
        $incidencia = Incidencias::find($id);
        $incidencia->status = false;
        $oldImage = $incidencia->imagen;
        Storage::disk('incidencias')->delete($oldImage);

        $incidencia->update();

        $response = [
            "status"=> true,
            "data"=>$incidencia,
            "message"=>"El registro fue eliminado correctamente"
        ];

        return response()->json($response);
    }
}
