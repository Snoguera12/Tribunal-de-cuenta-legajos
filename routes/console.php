<?php

use App\Models\Token;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(fn () => Token::purgarVencidos())->hourly();

// lo mas viejo de la auditoria se pasa a un archivo comprimido (no se borra)
Schedule::command('auditoria:archivar --si')
    ->monthlyOn(1, '03:15')
    ->onOneServer();

// que nadie haya tocado la auditoria
Schedule::command('auditoria:verificar')
    ->dailyAt('02:30')
    ->onOneServer();
