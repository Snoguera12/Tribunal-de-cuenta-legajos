<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// -mejor seguridad para no perder datos- backup automatico
// todos los dias a las 3am se guarda la base + documentos
Schedule::command('backup:run')
    ->dailyAt('03:00')
    ->onOneServer()
    ->emailOutputOnFailure(env('MAIL_ADMIN_ADDRESS'));

Schedule::command('backup:clean')
    ->dailyAt('03:30')
    ->onOneServer();
