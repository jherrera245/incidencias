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
use Illuminate\Support\Str;
use App\Services\FCMServices;

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
     * Metodo para obtener la lista de los tipos de incidencias
     */
    public function listaTiposIncidencias(){
        $tipos = DB::table('tipos_incidencias as tipo')
        ->select('tipo.id', 'tipo.nombre')
        ->where('tipo.status', '=', '1')
        ->get();

        $response = [
            "status"=>true,
            "tipos"=>$tipos
        ];

        return response()->json($response);
    }

    //validaciones para entradas de datos
    private function validatorInputs($inputs) {
        $validator = Validator::make($inputs, [
            'tipo'=>'required|integer',
            'descripcion'=>'required',
            'imagen'=>'required'
        ]);

        return $validator;
    }

    //metodo para el envio de notificaciones notifica a todos los administradores
    public function sendNotification(Request $request)
    {
        $topic = "reporte-incidencia";
        $id = $request->get('id');
        $incidencia = Incidencias::find($id);

        if($incidencia) {
            $fecha = date('d-m-Y', strtotime($incidencia->created_at));
            $body = "Incidencia reportada el {$fecha} fue procesada.\nDescripción: {$incidencia->descripcion}";
            
            $notification = [
                'body' => $body,
                'title' => 'Se reporto una incidencia!!'
            ];
    
            return FCMServices::sendNotificationByTopic($topic, $notification);
        }

        return response()->json(
            ['message'=>'No se pudo enviar la notificacion']
        );
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
        $validator = $this->validatorInputs($inputs);

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
        if($request->get("encode")) {
            $base64_image = $request->input('imagen');
            @list($type, $file_data) = explode(';', $base64_image);
            @list(, $file_data) = explode(',', $file_data); 
            $file = hash('sha256', Str::random(10).date('Y-m-d H:i:s')).".jpg"; //ciframos el nombre
            Storage::disk('incidencias')->put($file, base64_decode($file_data));
            $incidencia->imagen = $file;
        }else {
            if ($request->hasFile('imagen')) {
                $url = $request->file('imagen');
                $nombre = str_replace(' ', '-', trim($request->get('imagen')));
                $nombre = hash('sha256', $nombre.date('Y-m-d H:i:s')); //ciframos el nombre
                $file = $nombre.".".$url->guessExtension();
                //guardamos el archivo en el servidor
                Storage::disk('incidencias')->put($file, File::get($url));
                $incidencia->imagen = $file;
            }
        }

        $incidencia->save();

        // $this->sendNotificationrToUser();
        $notification = $this->sendNotification();

        $response = [
            "status"=>true,
            "message"=>"Datos insertados correctamente",
            'data' => $incidencia,
            'notification'=>$notification
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
        $validator = $this->validatorInputs($inputs);
        
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

        //guadar imagen
        if($request->get("encode")) {
            $base64_image = $request->input('imagen');
            Storage::disk('incidencias')->delete($oldImage); //borrar imagen anterior
            @list($type, $file_data) = explode(';', $base64_image);
            @list(, $file_data) = explode(',', $file_data); 
            $file = hash('sha256', Str::random(10).date('Y-m-d H:i:s')).".jpg"; //ciframos el nombre
            Storage::disk('incidencias')->put($file, base64_decode($file_data));
            $incidencia->imagen = $file;
        }else {
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
