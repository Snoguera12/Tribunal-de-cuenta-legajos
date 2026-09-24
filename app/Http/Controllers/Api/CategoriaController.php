<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use Illuminate\Http\Request;

class CategoriaController extends Controller
{
    public function index(){
        $categorias = Categoria::all();

        if($categorias->isEmpty()){
            $data = [
                'message' => 'No se encontraron Categorias.',
                'status' => 404
            ];
            return response()->json($data, 404);
        }

        return response()->json($categorias, 200);
    }

    public function show_id($id){
        $categoria = Categoria::find($id);

        if(!$categoria){
            $data = [
                'message' => 'Categoria no encontrado.',
                'status' => 404
            ];
            return response()->json($data, 404);
        }

        return response()->json($categoria, 200);
    }
}
