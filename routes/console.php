<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

<<<<<<< HEAD
// -mejor seguridad para no perder datos- backup automatico
// todos los dias a las 3am se guarda la base + documentos
Schedule::command('backup:run')
    ->dailyAt('03:00')
    ->onOneServer()
    ->emailOutputOnFailure(env('MAIL_ADMIN_ADDRESS'));

Schedule::command('backup:clean')
    ->dailyAt('03:30')
    ->onOneServer();
=======

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

Schedule::command('sistema:backup-limpiar')
    ->dailyAt('04:00')
    ->onOneServer();
>>>>>>> version2-20261014_210855
