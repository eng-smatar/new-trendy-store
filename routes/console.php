<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('mart:sync-catalog')->everySixHours()->withoutOverlapping();
Schedule::command('mart:sync-towns')->daily()->withoutOverlapping();
Schedule::command('mart:sync-orders')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('mart:resend-orders')->everyMinute()->withoutOverlapping();
