<?php

namespace App\Console\Commands;

use App\Models\Server;
use App\Models\ServerMonitoring;
use App\Telegram\SystemStats;
use DefStudio\Telegraph\Facades\Telegraph;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateMonitoringCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monitor';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */

    private SystemStats $systemStats;
    private bool $isConnected = false; // флаг для отслеживания подключения

    public function __construct()
    {
        parent::__construct();

        try {
            $server = Server::first();
            if (!$server) {
                throw new \Exception("Сервер не найден в базе данных.");
            }
            $this->systemStats = new SystemStats($server->hostname, $server->username, getenv('HOME') . '/.ssh/id_ed25519');
            $this->isConnected = true;

        } catch (\Exception $e) {
            $this->isConnected = false;
            Log::error("Ошибка при инициализации подключения к серверу: " . $e->getMessage());
        }


    }

    public function handle()
    {

        if (!$this->isConnected) {
            $this->error("Подключение к серверу не установлено. Операция отменена.");
            return;
        }

        try {
            $cpuUsage = $this->systemStats->getCpuUsage();
            $ramUsage = $this->systemStats->getRamUsage();
            $hddUsage = $this->systemStats->getHddUsage();

            $serverId = Server::pluck('id')->first();

//            DB::table('server_monitorings')->insert([
//                'server_id' => $serverId,
//                'last_cpu_usage' => $cpuUsage,
//                'last_ram_usage' => $ramUsage,
//                'last_hdd_usage' => $hddUsage,
//                'last_update' => now(),
//                'ssh_connection' => 'success',
//                'error_message' => null,
//            ]);

            ServerMonitoring::updateOrCreate(
              ['server_id' => $serverId],
              [
                'last_cpu_usage' => $cpuUsage,
                'last_ram_usage' => $ramUsage,
                'last_hdd_usage' => $hddUsage,
                'last_update' => now(),
                'ssh_connection' => 'success',
                'error_message' => null,

                ]

            );

            $message = "Статистика сервера:\n";
            $message .= "Использование CPU: $cpuUsage%\n";
            $message .= "Использование RAM: $ramUsage%\n";
            $message .= "Места на диске: $hddUsage\n";

//            Telegraph::chat('406210384')->message($message)->send();




        } catch (\Exception $e){

//            DB::table('server_monitorings')->insert([
//                'server_id' => $serverId,
//                'last_cpu_usage' => null,
//                'last_ram_usage' => null,
//                'last_hdd_usage' => null,
//                'last_update' => now(),
//                'ssh_connection' => 'error',
//                'error_message' => $e->getMessage(),
//            ]);


            ServerMonitoring::updateOrCreate(
                ['server_id' => $serverId],
                [
                    'last_cpu_usage' => null,
                    'last_ram_usage' => null,
                    'last_hdd_usage' => null,
                    'last_update' => now(),
                    'ssh_connection' => 'error',
                    'error_message' => $e->getMessage(),

                ]
            );
        }

    }
}
