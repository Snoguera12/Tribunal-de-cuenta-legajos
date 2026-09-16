<?php

namespace App\Filament\Resources\Personas\Widgets;

use App\Models\Persona;
use Filament\Widgets\ChartWidget;

class GeneroWidget extends ChartWidget
{
    protected ?string $heading = 'Género';
    protected ?string $maxHeight = '200px';
    protected ?string $maxWidth = '5xl';
    public static function canView(): bool
    {
        return auth()->user()->isAdmin_RRHH_Funcionario();
    }
    protected function getData(): array
    {
        $femenino = Persona::query()->where('genero', 0)->count();
        $masculino = Persona::query()->where('genero', 1)->count();
        $otro = Persona::query()->where('genero', 2)->count();
        return [
            'datasets' =>[
                [
                    'label' => 'Género',
                    'data' => [$femenino, $masculino, $otro],
                    'backgroundColor' => [
                        'rgb(238, 59, 170)',
                        'rgb(49, 58, 220)',
                        'rgb(56, 223, 83)',
                    ],
                ],
            ],
            'labels' => ['Femenino', 'Masculino', 'Otro'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
        //return 'pie';
    }
}
