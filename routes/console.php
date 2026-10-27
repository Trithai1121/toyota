<?php

use App\Console\Commands\PublishScheduledPages;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('pages:publish-scheduled', fn () => app(PublishScheduledPages::class)->handle())
    ->purpose('Publish pages after their scheduled time');

Schedule::command('pages:publish-scheduled')->everyMinute()->withoutOverlapping();
