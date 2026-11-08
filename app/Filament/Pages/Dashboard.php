<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Documentos\Widgets\DocumentosPendientesWidget;
use App\Filament\Resources\Legajos\Widgets\LegajoWidget;
use App\Filament\Resources\Personas\Widgets\GeneroWidget;
use App\Filament\Resources\Personas\Widgets\PersonaTotalWidget;
use App\Filament\Widgets\CompletitudFichaWidget;
use App\Filament\Widgets\MiLegajoWidget;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class Dashboard extends BaseDashboard
{
    // El orden de estas listas es el orden en pantalla.
    protected const WIDGETS_ESTADISTICAS = [
        DocumentosPendientesWidget::class,
        PersonaTotalWidget::class,
        LegajoWidget::class,
        GeneroWidget::class,
    ];

    protected const WIDGETS_FICHA = [
        MiLegajoWidget::class,
        CompletitudFichaWidget::class,
    ];
    private function hayWidgetsVisibles(array $widgets): bool
    {
        return collect($widgets)->contains(fn (string $widget) => $widget::canView());
    }
    public function content(Schema $schema): Schema
    {
        $verEstadisticas = $this->hayWidgetsVisibles(self::WIDGETS_ESTADISTICAS);
        $verFicha = $this->hayWidgetsVisibles(self::WIDGETS_FICHA);

        $estadisticas = $this->getWidgetsSchemaComponents(self::WIDGETS_ESTADISTICAS);
        $ficha = $this->getWidgetsSchemaComponents(self::WIDGETS_FICHA);

        // Una sola sección: sin barra de pestañas.
        if ($verEstadisticas xor $verFicha) {
            return $schema->components([
                Grid::make($this->getColumns())
                    ->schema($verFicha ? $ficha : $estadisticas),
            ]);
        }

        return $schema->components([
            Tabs::make('Tabs_escritorio')
                ->columnSpanFull()
                ->persistTabInQueryString()
                ->tabs([
                    Tab::make('Tab Estadísticas')
                        ->label('Estadísticas')
                        ->id('estadisticas')
                        ->icon('heroicon-m-chart-bar')
                        ->schema([Grid::make($this->getColumns())->schema($estadisticas)]),

                    Tab::make('Tab Mi Ficha')
                        ->label('Mi ficha')
                        ->id('ficha')
                        ->icon('heroicon-m-clipboard-document-check')
                        ->schema([Grid::make($this->getColumns())->schema($ficha)]),
                ]),
        ]);
    }
}