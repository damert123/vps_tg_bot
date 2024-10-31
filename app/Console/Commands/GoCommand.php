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

       $time =  now()->setTimezone('Europe/Moscow');
       dd($time);

//        $server = Server::find(5);
//
//
//
//        $this->ssh = new SSH2($server->hostname);
//        $key = PublicKeyLoader::load(file_get_contents(getenv('HOME') . '/.ssh/id_ed25519'));
//
//        if (!$this->ssh->login($server->username, $key)){
//            throw new \Exception("Не удалось подключиться к серверу {$server->hostname} через SSH");
//        }
//
//        $output = $this->ssh->exec("top -bn1 | grep '%Cpu'");
//        // Обработка данных (парсинг строки)
//        preg_match('/(\d+[\.,]\d+)\s+id/', $output, $matches);
//        $cpuIdle = floatval($matches[0] ?? 0);  // Процент простоя
//        $cpuUsage = 100 - $cpuIdle;  // Занятость CPU
//
//        dd($cpuUsage);



    }
}
