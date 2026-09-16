<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum TipodocEnum: int implements HasLabel
{
    case DNI = 0;
    case Titulo = 1;
    case Cursos = 2;
    case Licencia = 3;
    case Acta_de_Nacimiento = 4;
    case Certificado_de_Escolaridad = 5;
    case Certificado_Defuncion = 6;
    case Certificado_de_Casamiento = 7;
    case Sumario = 8;
    case Resolucion = 9;
    case Foto_de_Perfil = 10;
    case Curriculum = 11;
    case Otro = 12;
    public function getLabel(): ?string
    {
        return match ($this) {
            self::DNI => 'DNI',
            self::Titulo => 'Título',
            self::Cursos => 'Cursos',
            self::Licencia => 'Licencia',
            self::Acta_de_Nacimiento => 'Acta de Nacimiento',
            self::Certificado_de_Escolaridad => 'Certificado de Escolaridad',
            self::Certificado_Defuncion => 'Certificado Defunción',
            self::Certificado_de_Casamiento => 'Certificado de Casamiento',
            self::Sumario => 'Sumario',
            self::Resolucion => 'Resolución',
            self::Foto_de_Perfil => 'Foto de Perfil',
            self::Curriculum => 'Curriculum',
            self::Otro => 'Otro',
        };
    }
}