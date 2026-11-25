<?php

use Illuminate\Support\Facades\Schedule;

// Mart: poll stock every 5-15 minutes (each pass re-reads prices too); descriptions rarely change.
Schedule::command('mart:sync-catalog --quick')->everyFifteenMinutes()->withoutOverlapping();
Schedule::command('mart:sync-catalog')->everySixHours()->withoutOverlapping();
Schedule::command('mart:sync-towns')->daily()->withoutOverlapping();
Schedule::command('mart:sync-orders')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('mart:resend-orders')->everyMinute()->withoutOverlapping();
