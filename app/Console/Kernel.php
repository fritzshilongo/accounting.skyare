<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('invoices:process-recurring')->daily();
        $schedule->command('db:backup')->dailyAt('00:00');
    }

    protected function commands(): void
    {
        $commandsPath = __DIR__ . '/Commands';
        if (is_dir($commandsPath)) {
            $this->load($commandsPath);
        }

        if (file_exists(base_path('routes/console.php'))) {
            require base_path('routes/console.php');
        }
    }
}