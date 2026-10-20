<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;

class Titulo extends Model
{
    protected $fillable = [
        'nombre',
    ];
    public function estudioTitulos() {
        return $this->hasMany(EstudioTitulo::class);
    }

    public static function getFormSchema(string $modeForm): array{
        $resultado = match($modeForm){
            'table' => [
                TextInput::make('nombre')
                ->label('Nombre del Título')
                ->maxLength(100)
                ->unique(ignoreRecord: true)
                ->required(),
            ],
            'list' => [],
        };

        return $resultado;
    }
    public static function getOutSchema(string $mode_out) : array{
        $resultado = match ($mode_out) {
            'table' => [
                TextColumn::make('nombre')
                ->label('Nombre del Título')
                ->sortable()
                ->searchable()
                ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('created_at')
                ->label('Fecha de Creación')
                ->dateTime('d/m/Y H:i:s')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
            ],
            'list' => [
                TextEntry::make('nombre')
                ->hiddenLabel()
                ->visible(fn ($record) => $record->nombre != 'otro')
                ->icon('heroicon-m-academic-cap'),
            ],
        };
        return $resultado;
    }
    public function fusionarEn(Titulo $destino): void
    {
        DB::transaction(function () use ($destino) {
            $this->estudioTitulos()->get()->each(function (EstudioTitulo $et) use ($destino) {
                EstudioTitulo::where(
                    'estudio_id', $et->estudio_id)->where('titulo_id', $destino->id)->exists()
                    ? $et->delete()
                    : $et->update(['titulo_id' => $destino->id]);
            });

            $this->delete();
        });
    }
}
