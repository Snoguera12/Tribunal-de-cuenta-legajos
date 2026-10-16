<?php

namespace App\Filament\Pages;

use App\Models\Auditoria;
use App\Services\CopiaDeSeguridad;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;


// solo administrador
class CopiasDeSeguridad extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-circle-stack';
    protected static string|\UnitEnum|null $navigationGroup = 'Seguridad';
    protected static ?string $navigationLabel = 'Copias de seguridad';
    protected static ?string $title = 'Copias de seguridad';
    protected static ?int $navigationSort = 13;

    protected string $view = 'filament.pages.copias-de-seguridad';


    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }


    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);
    }


    public function copias(): array
    {
        abort_unless(static::canAccess(), 403);

        return app(CopiaDeSeguridad::class)->listar();
    }


    protected function getHeaderActions(): array
    {
        return [

            Action::make('completo')
                ->label('Backup completo ahora')
                ->icon('heroicon-m-arrow-down-on-square')
                ->requiresConfirmation()
                ->action(fn () => $this->hacer('completo')),

            Action::make('solo_db')
                ->label('Solo bases')
                ->color('gray')
                ->requiresConfirmation()
                ->action(fn () => $this->hacer('db')),

        ];
    }


    private function hacer(string $tipo): void
    {
        abort_unless(static::canAccess(), 403);

        try {
            $info = app(CopiaDeSeguridad::class)->crear($tipo, $tipo === 'completo');

            Notification::make()->title('Backup listo: ' . $info['nombre'])->success()->send();

        } catch (\Throwable $e) {

            Notification::make()->title('El backup falló')->body($e->getMessage())->danger()->send();
        }
    }


    // el nombre tiene que ser uno de los que existen (evita rutas raras)
    private function nombreValido(string $nombre): bool
    {
        return in_array($nombre, array_column($this->copias(), 'nombre'), true);
    }


    public function verificar(string $nombre): void
    {
        abort_unless($this->nombreValido($nombre), 404);

        $copia = app(CopiaDeSeguridad::class);

        $local = $copia->rutaLocal($nombre);
        $resultado = $copia->verificar($local);
        @unlink($local);

        $ok = $resultado['ok'] && $copia->hashCorrecto($nombre);

        Auditoria::registrar(
            evento: $ok ? 'backup_verificado' : 'backup_fallo',
            descripcion: ($ok ? 'Verificó el backup ' : 'El backup no pasó la verificación: ') . $nombre,
            extra: ['errores' => implode(' | ', $resultado['errores'])],
        );

        Notification::make()
            ->title($ok ? 'Backup íntegro' : 'Backup con problemas')
            ->body($ok ? null : implode("\n", $resultado['errores']))
            ->{$ok ? 'success' : 'danger'}()
            ->send();
    }


    public function descargar(string $nombre)
    {
        abort_unless($this->nombreValido($nombre), 404);

        Auditoria::registrar(
            evento: 'backup_descargado',
            descripcion: 'Descargó el backup ' . $nombre,
            extra: ['archivo' => $nombre],
        );

        return Storage::disk(config('sistema.backup.discos')[0] ?? 'backups')->download($nombre);
    }
}
