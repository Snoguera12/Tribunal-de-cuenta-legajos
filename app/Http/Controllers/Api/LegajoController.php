<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Legajo;
use Illuminate\Http\Request;

class LegajoController extends Controller
{
    public function index(){
        $legajos = Legajo::all();
        if($legajos->isEmpty()){
            $data = [
                'message' => 'No se encontraron Legajos.',
                'status' => 404
            ];
            return response()->json($data, 404);
        }
        return response()->json($legajos, 200);
    }
}
