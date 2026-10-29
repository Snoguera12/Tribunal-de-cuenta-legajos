<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


// backup completo todos los dias
Schedule::command('sistema:backup')
    ->dailyAt('03:00')
    ->onOneServer();

// solo las bases cada 6 horas
Schedule::command('sistema:backup --tipo=db')
    ->everySixHours()
    ->onOneServer();

// se revisa que el backup exista y no este roto
Schedule::command('sistema:backup-verificar')
    ->dailyAt('03:30')
    ->onOneServer();

// resumen de las ultimas 24 horas (solo si ALERTAS_CORREO=true)
Schedule::command('sistema:resumen-diario')
    ->dailyAt('07:00')
    ->onOneServer();

// lo mas viejo de la auditoria se pasa a un archivo comprimido
Schedule::command('auditoria:archivar --si')
    ->monthlyOn(1, '03:15')
    ->onOneServer();

// que nadie haya tocado la auditoria
Schedule::command('auditoria:verificar')
    ->dailyAt('02:30')
    ->onOneServer();

// los "ultimos agentes" de mas de 90 dias se borran
Schedule::command('actividad:limpiar --dias=90')
    ->weeklyOn(0, '04:30')
    ->onOneServer();

// latido: el diagnostico (sistema:diagnostico) mira esta marca para saber si el cron de Laravel esta corriendo
Schedule::call(fn () => \Illuminate\Support\Facades\Cache::put('sistema.scheduler_latido', now()->timestamp, 600))
    ->name('latido-del-programador')
    ->everyMinute();

Schedule::command('sistema:backup-limpiar')
    ->dailyAt('04:00')
    ->onOneServer();
