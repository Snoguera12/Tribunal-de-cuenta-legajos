<?php

namespace Tests\Feature;

use App\Models\Documento;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;


class HuerfanosTest extends TestCase
{
    use RefreshDatabase;

    protected array $connectionsToTransact = [null, 'historico'];


    public function test_detecta_y_borra_solo_los_archivos_que_nadie_usa_y_no_son_recientes(): void
    {
        Storage::fake('local');
        $disco = Storage::disk('local');

        foreach (['documentos/usado.pdf', 'documentos/borrado_suave.pdf', 'documentos/huerfano.pdf', 'documentos/reciente.pdf'] as $archivo) {
            $disco->put($archivo, 'x');
        }

        Documento::create(['ruta' => 'documentos/usado.pdf', 'descripcion' => 'a']);
        Documento::create(['ruta' => 'documentos/borrado_suave.pdf', 'descripcion' => 'b'])->delete();
        Documento::create(['ruta' => 'documentos/no_existe.pdf', 'descripcion' => 'c']);

        // los archivos viejos tienen mas de un dia, el "reciente" se acaba de subir
        foreach (['usado', 'borrado_suave', 'huerfano'] as $nombre) {
            touch($disco->path("documentos/{$nombre}.pdf"), now()->subDays(3)->timestamp);
        }

        Artisan::call('documentos:huerfanos');
        $this->assertTrue($disco->exists('documentos/huerfano.pdf'), 'sin --borrar no se toca nada');

        Artisan::call('documentos:huerfanos', ['--borrar' => true]);

        $this->assertFalse($disco->exists('documentos/huerfano.pdf'));
        $this->assertTrue($disco->exists('documentos/usado.pdf'));
        $this->assertTrue($disco->exists('documentos/borrado_suave.pdf'), 'un borrado suave se puede restaurar');
        $this->assertTrue($disco->exists('documentos/reciente.pdf'), 'puede estar por guardarse');
    }
}
