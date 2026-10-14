<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('berita:fetch')->hourly();
Schedule::command('info:fetch')->weeklyOn(1, '02:00');
Schedule::command('wiki:sync')->monthlyOn(1, '03:00');
