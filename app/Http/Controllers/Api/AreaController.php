<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Area;
use Illuminate\Http\Request;

class AreaController extends Controller
{
    public function index(){
        $areas = Area::all();

        if($areas->isEmpty()){
            $data = [
                'message' => 'No se encontraron Áreas.',
                'status' => 404
            ];
            return response()->json($data, 404);
        }

        return response()->json($areas, 200);
    }

    public function show($id){
        $area = Area::find($id);

        if(!$area){
            $data = [
                'message' => 'Área no encontrado.',
                'status' => 404
            ];
            return response()->json($data, 404);
        }

        $data = [
            'area' => $area,
            'status' => 200
        ];

        return response()->json($data, 200);
    }
}
