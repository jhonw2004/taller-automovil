<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// 014-notificaciones: password.expirada, único trigger basado en el paso del tiempo (los otros 9
// se disparan desde eventos/observers de sus features origen).
Schedule::command('notificaciones:passwords-por-vencer')->daily();
