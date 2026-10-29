<?php

namespace App\Filament\Concerns;

use App\Models\Auditoria;
use Symfony\Component\HttpFoundation\StreamedResponse;


// informe en csv con lo que se ve en la tabla (respeta filtros y busqueda)
trait DescargaInformeCsv
{

    // $columnas: titulo => funcion que saca el valor de cada fila
    protected function descargarCsv(string $nombre, array $columnas, string $evento = 'exportacion'): StreamedResponse
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $consulta = $this->getFilteredSortedTableQuery();
        $filas = 0;

        Auditoria::registrar(
            evento: $evento,
            descripcion: "Descargó el informe {$nombre}",
            extra: [
                'informe' => $nombre,
                'filtros' => json_encode($this->tableFilters ?? [], JSON_UNESCAPED_UNICODE),
                'busqueda' => (string) ($this->tableSearch ?? ''),
            ],
        );

        return response()->streamDownload(function () use ($consulta, $columnas, &$filas) {

            $salida = fopen('php://output', 'w');

            // para que excel lea bien los acentos
            fwrite($salida, "\xEF\xBB\xBF");
            fputcsv($salida, array_keys($columnas), ';');

            $consulta->chunk(500, function ($lote) use ($salida, $columnas, &$filas) {
                foreach ($lote as $fila) {
                    fputcsv($salida, array_map(fn ($sacar) => self::celda($sacar($fila)), $columnas), ';');
                    $filas++;
                }
            });

            fclose($salida);

        }, $nombre . '_' . now()->format('Ymd_His') . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }


    // los valores que empiezan con = + - @ se tomarian como formula en excel
    protected static function celda($valor): string
    {
        if (is_array($valor)) {
            $valor = json_encode($valor, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $valor = (string) $valor;

        return preg_match('/^[=+\-@\t\r]/', $valor) ? "'" . $valor : $valor;
    }
}
