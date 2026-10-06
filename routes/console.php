<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('evaluations:update-statuses')
    ->hourly()
    ->timezone(config('evaluation.timezone'))
    ->withoutOverlapping();
