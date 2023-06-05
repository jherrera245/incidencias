<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;

class ReporteController extends Controller
{
    public function reportAll(Request $request){
        if ($request) {
            
            $empleado = trim($request->get('empleado'));
            $from = trim($request->get('from'));
            $to = trim($request->get('to'));

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
            ->where(function($groupQuery) use ($empleado, $from, $to){
                if($empleado != 0) {
                    $groupQuery->where('emp.id','=', $empleado)->orWhereBetween('in.created_at', [$from, $to]);
                }else {
                    $groupQuery->whereBetween('in.created_at', [$from, $to]);   
                }
            })
            ->where('in.status','=','1')
            ->orderBy('in.id','desc')
            ->get();

            return response()->json($incidencias);
        }
    }

    public function reportById(Request $request) {
        if($request) {
            $id = trim($request->get('id'));
            $incidencia = DB::table('incidencias AS in')
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
            ->where('in.id', '=', $id)
            ->where('in.status','=','1')
            ->first();

            $retroalimentacion = DB::table('retroalimentaciones AS re')
            ->join('incidencias AS in', 're.id_incidencia', '=', 'in.id')
            ->join('users AS u', 're.id_usuario_resolucion', '=', 'u.id')
            ->join('empleados as emp', 'u.id_empleado', '=', 'emp.id')
            ->join('cargos as car', 'emp.id_cargo','=','car.id')
            ->join('departamentos as dep', 'emp.id_departamento','=','dep.id')
            ->select(
                're.id', 'emp.nombres', 'emp.apellidos', 're.descripcion AS retroalimentacion', 
                'emp.nombres', 'emp.apellidos', 'car.nombre AS cargo', 
                'dep.nombre AS departamento', 're.status', 're.created_at AS fecha'
            )
            ->where('in.id', '=', $id)
            ->first();

            return response()->json([
                "incidencia" => $incidencia,
                "retroalimentacion" => $retroalimentacion
            ]);
        }
    }
}
