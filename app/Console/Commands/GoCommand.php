<?php

namespace App\Console\Commands;

use App\Models\Server;
use Illuminate\Console\Command;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Net\SSH2;

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

        $server = Server::find(5);



        $this->ssh = new SSH2($server->hostname);
        $key = PublicKeyLoader::load(file_get_contents(getenv('HOME') . '/.ssh/id_ed25519'));

        if (!$this->ssh->login($server->username, $key)){
            throw new \Exception("Не удалось подключиться к серверу {$server->hostname} через SSH");
        }

        $output = $this->ssh->exec("top -bn1 | grep '%Cpu'");
        // Обработка данных (парсинг строки)
        preg_match('/(\d+[\.,]\d+)\s+id/', $output, $matches);
        $cpuIdle = floatval($matches[0] ?? 0);  // Процент простоя
        $cpuUsage = 100 - $cpuIdle;  // Занятость CPU

        dd($cpuUsage);


//        $output = shell_exec("df -h --total | grep 'total'");
//        $disk = preg_split('/\s+/', $output);
//        $totalDisk = $disk[1];   // Всего места на диске
//        $usedDisk = $disk[2];    // Использовано
//        $availableDisk = $disk[3];  // Доступно
//        dd($availableDisk);
    }
}
