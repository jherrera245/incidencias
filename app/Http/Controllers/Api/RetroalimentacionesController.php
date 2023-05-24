<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Retroalimentaciones;
use App\Models\Incidencias;
use App\Models\TokensUsers;
use Illuminate\Http\Request;
use DB;
use Validator;
use App\Services\FCMServices;

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

    //metodo para el envio de notificaciones notifica a todos los administradores
    public function sendNotification(Request $request)
    {
        $id = $request->get('id');
        $retroalimentacion = Retroalimentaciones::find($id);

        if ($retroalimentacion) {
            
            $incidencia = Incidencias::find($retroalimentacion->id_incidencia);

            //buscamos el ultimo token de usuario registrado
            $tokenUser = TokensUsers::where('id_usuario', $incidencia->id_usuario)->latest('created_at')->first();

            if ($tokenUser) {

                $token = $tokenUser->token;
                $fecha = date('d-m-Y', strtotime($incidencia->created_at));
                $body = "Su incidencia reportada el {$fecha} fue procesada.\nDescripción: {$retroalimentacion->descripcion}";
                
                $notification = [
                    'body' => $body,
                    'title' => 'Retroalimentación'
                ];

                return FCMServices::sendNotificationByToken($token, $notification);
            }

            return response()->json(
                ['message'=>'El usuario no tiene un token valido']
            );
        }else {     
            return response()->json(
                ['message'=>'No se pudo enviar la notificacion']
            );
        }
    }

    //funcion para cambiar el estado de una incidencia
    private function changeStatusIncidencia(Request $request) {
        $incidencia = Incidencias::find($request->get('id_incidencia'));
        $incidencia->status_resolucion = $request->get('status');
        $incidencia->update();
    }

    //validaciones para entradas de datos
    private function validatorInputs($inputs) {
        $validator = Validator::make($inputs, [
            'id_incidencia'=>'required',
            'descripcion'=>'required',
            'status'=>'required',
        ]);

        return $validator;
    }

    public function store(Request $request)
    {

        $user = auth()->user();
        $inputs = $request->all();

        try {
            DB::beginTransaction();

            //validacion de entradas
            $validator = $this->validatorInputs($inputs);

            if ($validator->fails()) {
                $response = [
                    "status"=> false,
                    "errors"=>$validator->errors()
                ];

                return response()->json($response);
            }

            //se almacena la retroalimentacion
            $retroalimentaciones = new Retroalimentaciones();
            $retroalimentaciones->id_incidencia = $request->get('id_incidencia');
            $retroalimentaciones->id_usuario_resolucion = $user->id;
            $retroalimentaciones->descripcion = $request->get('descripcion');    
            $retroalimentaciones->save();

            //buscamos la incidencia y actualizamos su estado de resolucion
            $this->changeStatusIncidencia($request);

            $response = [
                "status"=>true,
                "message"=>"Datos insertados correctamente",
                'data' => $retroalimentaciones
            ];

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();

            $response = [
                "status"=>true,
                "message"=>"Error al insertar intenta de nuevo",
            ];
        }

        return response()->json($response);
    }

    public function update(Request $request, $id)
    {
        $user = auth()->user();
        $inputs = $request->all();

        try {
            DB::beginTransaction();

            //validacion de entradas
            $validator = $this->validatorInputs($inputs);

            if ($validator->fails()) {
                $response = [
                    "status"=> false,
                    "errors"=>$validator->errors()
                ];

                return response()->json($response);
            }

            //se almacena la retroalimentacion
            $retroalimentaciones = Retroalimentaciones::find($id);
            $retroalimentaciones->id_incidencia = $request->get('id_incidencia');
            $retroalimentaciones->id_usuario_resolucion = $user->id;
            $retroalimentaciones->descripcion = $request->get('descripcion');    
            $retroalimentaciones->update();

            //buscamos la incidencia y actualizamos su estado de resolucion
            $this->changeStatusIncidencia($request);

            $response = [
                "status"=>true,
                "message"=>"Datos actualizados correctamente",
                'data' => $retroalimentaciones
            ];

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();

            $response = [
                "status"=>true,
                "message"=>"Error al actualizar intenta de nuevo",
            ];
        }

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
