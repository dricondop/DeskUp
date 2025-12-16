<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;


Schedule::command('cleaning:run')->everyMinute();
Schedule::command('events:complete-expired')->everyFiveMinutes();