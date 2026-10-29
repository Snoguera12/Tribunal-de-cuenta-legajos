<?php

namespace App\Filament\Resources\Auditorias\Schemas;

use App\Models\Auditoria;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;


class AuditoriaInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
        ->components([

            Section::make('Qué pasó')
            ->columns(3)
            ->columnSpanFull()
            ->schema([
                TextEntry::make('created_at')->label('Fecha')->dateTime('d/m/Y H:i:s'),
                TextEntry::make('evento')->label('Evento')
                ->badge()
                ->formatStateUsing(fn (string $state) => Auditoria::eventos()[$state] ?? $state),
                TextEntry::make('severidad')->label('Nivel')->badge()
                ->formatStateUsing(fn (string $state) => Auditoria::severidades()[$state] ?? $state),
                TextEntry::make('categoria')->label('Categoría')
                ->formatStateUsing(fn (string $state) => Auditoria::categorias()[$state] ?? $state),
                TextEntry::make('modelo')->label('Registro afectado')
                ->formatStateUsing(fn (Auditoria $registro) => $registro->modelo ? $registro->modelo . ' #' . $registro->modelo_id : '-'),
                TextEntry::make('descripcion')->label('Descripción')->columnSpanFull(),
            ]),


            Section::make('Quién y desde dónde')
            ->columns(3)
            ->columnSpanFull()
            ->schema([
                TextEntry::make('actor_nombre')->label('Actor')->placeholder('-'),
                TextEntry::make('actor_rol')->label('Rol')->placeholder('-'),
                TextEntry::make('actor_tipo')->label('Tipo de actor'),
                TextEntry::make('ip')->label('IP')->placeholder('-'),
                TextEntry::make('mac')->label('MAC')->placeholder('no disponible'),
                TextEntry::make('sistema_operativo')->label('Equipo / navegador')->placeholder('-'),
                TextEntry::make('dispositivo')->label('Dispositivo')->placeholder('-'),
                TextEntry::make('sesion')->label('Sesión (huella)')->placeholder('-'),
                TextEntry::make('peticion')->label('Id de petición')->placeholder('-'),
                TextEntry::make('metodo')->label('Método')->placeholder('-'),
                TextEntry::make('navegador')->label('User agent')->placeholder('-'),
                TextEntry::make('url')->label('Página')->placeholder('-')->columnSpanFull(),
            ]),


            Section::make('Valores anteriores')
            ->columnSpanFull()
            ->visible(fn (Auditoria $registro) => filled($registro->valores_anteriores))
            ->schema([
                KeyValueEntry::make('valores_anteriores')
                ->hiddenLabel()
                ->keyLabel('Campo')
                ->valueLabel('Valor'),
            ]),


            Section::make('Valores nuevos')
            ->columnSpanFull()
            ->visible(fn (Auditoria $registro) => filled($registro->valores_nuevos))
            ->schema([
                KeyValueEntry::make('valores_nuevos')
                ->hiddenLabel()
                ->keyLabel('Campo')
                ->valueLabel('Valor'),
            ]),


            Section::make('Datos extra')
            ->columnSpanFull()
            ->visible(fn (Auditoria $registro) => filled($registro->extra))
            ->schema([
                KeyValueEntry::make('extra')
                ->hiddenLabel()
                ->keyLabel('Dato')
                ->valueLabel('Valor'),
            ]),

        ]);
    }
}
