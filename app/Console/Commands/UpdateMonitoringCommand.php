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


    public function handle()
    {

        $servers = Server::all(); // Получаем все серваки

        if ($servers->isEmpty()) {
            $this->error("Не найдено серверов для мониторинга.");
            return;
        }

//        if (!$this->isConnected) {
//            $this->error("Подключение к серверу не установлено. Операция отменена.");
//            return;
//        }

        foreach ($servers as $server){
            try {
                $this->systemStats = new SystemStats($server->hostname, $server->username, getenv('HOME') . '/.ssh/id_ed25519');
                $cpuUsage = $this->systemStats->getCpuUsage();
                $ramUsage = $this->systemStats->getRamUsage();
                $hddUsage = $this->systemStats->getHddUsage();

                ServerMonitoring::updateOrCreate(
                    ['server_id' => $server->id],
                    [
                        'last_cpu_usage' => $cpuUsage,
                        'last_ram_usage' => $ramUsage,
                        'last_hdd_usage' => $hddUsage,
                        'last_update' => now()->setTimezone('Europe/Moscow'),
                        'ssh_connection' => 'success',
                        'error_message' => null,
                    ]
                );
            }catch (\Exception $e){
                ServerMonitoring::updateOrCreate(
                    ['server_id' => $server->id],
                    [
                        'last_cpu_usage' => null,
                        'last_ram_usage' => null,
                        'last_hdd_usage' => null,
                        'last_update' => now()->setTimezone('Europe/Moscow'),
                        'ssh_connection' => 'error',
                        'error_message' => $e->getMessage(),
                    ]
                );
            }
        }

    }
}
