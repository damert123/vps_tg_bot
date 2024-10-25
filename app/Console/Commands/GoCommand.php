<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GoCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'go';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
//        $output = shell_exec("free -m");
//        $lines = explode("\n", trim($output));
//        $memory = preg_split('/\s+/', $lines[1]);
//        $totalRam = $memory[1];  // Всего памяти
//        $usedRam = $memory[2];   // Занято
//        $ramUsage = ($usedRam / $totalRam) * 100;
//        dd(number_format($ramUsage, 2)) ;  // Округление до 2 знаков

//        $output = shell_exec("cat ~/.ssh/id_rsa.pub");
        $home = getenv('HOME') . '/.ssh/id_rsa';
        dd($home);


//        $output = shell_exec("df -h --total | grep 'total'");
//        $disk = preg_split('/\s+/', $output);
//        $totalDisk = $disk[1];   // Всего места на диске
//        $usedDisk = $disk[2];    // Использовано
//        $availableDisk = $disk[3];  // Доступно
//        dd($availableDisk);
    }
}
