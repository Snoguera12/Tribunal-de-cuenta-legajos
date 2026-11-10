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

// recordatorios del día para administrador y RRHH (campanita del panel)
Schedule::command('recordatorios:enviar')
    ->dailyAt('07:30')
    ->onOneServer();

// ---------------------------------------------------------------- copias de seguridad

// backup completo (base + documentos + configuración) todos los días
Schedule::command('sistema:backup')
    ->dailyAt('03:00')
    ->onOneServer();

// solo la base, cada 6 horas
Schedule::command('sistema:backup --tipo=db')
    ->everySixHours()
    ->onOneServer();

// se revisa que el backup exista, sea reciente y no esté roto
Schedule::command('sistema:backup-verificar')
    ->dailyAt('03:30')
    ->onOneServer();

// se borran los backups que ya pasaron el tiempo de retención
Schedule::command('sistema:backup-limpiar')
    ->dailyAt('04:00')
    ->onOneServer();

// resumen de las últimas 24 horas por correo (solo si ALERTAS_CORREO=true)
Schedule::command('sistema:resumen-diario')
    ->dailyAt('07:00')
    ->onOneServer();
