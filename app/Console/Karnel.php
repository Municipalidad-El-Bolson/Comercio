<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('ubicaciones:marcar-irregulares')->dailyAt('00:15');
        $schedule->command('vto:alert-proximos')->dailyAt('08:00')->timezone('America/Argentina/Salta');
        $schedule->command('vto:mark-vencidos')->hourly()->timezone('America/Argentina/Salta');
        $schedule->command('vto:rebuild')->dailyAt('01:05');
        $schedule->command('vto:notify-proximos --days=10')->dailyAt('01:10');
    }

    protected $commands = [
        \App\Console\Commands\VtoRebuild::class,
        \App\Console\Commands\VtoNotifyProximos::class,
        \App\Console\Commands\NormalizarEstados::class,
    ];

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}
