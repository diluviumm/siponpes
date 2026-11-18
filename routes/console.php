<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('billing:generate-monthly')->monthlyOn(1, '00:05');
Schedule::command('billing:send-overdue-reminders')->dailyAt('00:10');
Schedule::command('billing:expire-overdue')->dailyAt('00:15');
Schedule::command('backup:database')->dailyAt('02:00');
