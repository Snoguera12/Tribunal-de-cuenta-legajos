<?php

namespace App\Filament\Pages;

use App\Services\HistoricoService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;


// solo administrador: versiones anteriores de cada registro (base aparte)
class HistorialDatos extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-archive-box';
    protected static string|\UnitEnum|null $navigationGroup = 'Seguridad';
    protected static ?string $navigationLabel = 'Historial de datos';
    protected static ?string $title = 'Historial de datos';
    protected static ?int $navigationSort = 12;

    protected string $view = 'filament.pages.historial-datos';

    public ?string $tabla = 'personas';
    public ?string $registro = null;


    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }


    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);
    }


    public function tablas(): array
    {
        return config('sistema.tablas_historico');
    }


    // si no hay id: los ultimos cambios de la tabla. Con id: todas las versiones de ese registro
    public function versiones()
    {
        abort_unless(static::canAccess(), 403);

        if (! in_array($this->tabla, $this->tablas(), true)) {
            return collect();
        }

        $historica = HistoricoService::nombre($this->tabla);

        if (! HistoricoService::esquema()->hasTable($historica)) {
            return collect();
        }

        if ($this->registro !== null && $this->registro !== '') {
            return HistoricoService::versiones($this->tabla, $this->registro);
        }

        return DB::connection('historico')->table($historica)->orderByDesc('hist_id')->limit(50)->get();
    }


    public function restaurar(int $version): void
    {
        abort_unless(static::canAccess(), 403);
        abort_unless(in_array($this->tabla, $this->tablas(), true) && $this->registro, 404);

        $codigo = Artisan::call('historico:restaurar', [
            'tabla' => $this->tabla,
            'id' => $this->registro,
            'version' => $version,
            '--si' => true,
        ]);

        Notification::make()
            ->title($codigo === 0 ? 'Registro restaurado' : 'No se pudo restaurar')
            ->{$codigo === 0 ? 'success' : 'danger'}()
            ->send();
    }
}
