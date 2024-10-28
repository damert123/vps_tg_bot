<?php

namespace App\Telegram;

use App\Models\Server;
use DefStudio\Telegraph\Facades\Telegraph;
use DefStudio\Telegraph\Handlers\WebhookHandler;
use DefStudio\Telegraph\Keyboard\Button;
use DefStudio\Telegraph\Keyboard\Keyboard;
use DefStudio\Telegraph\Models\TelegraphChat;
use Illuminate\Support\Stringable;

class Handler extends WebhookHandler
{

    private SystemStats $systemStats;
    private ServerMine $serverMine;


    /**
     * @throws \Exception
     */
    public function __construct() {
        $server = Server::first();
        $this->systemStats = new SystemStats($server->hostname, $server->username, getenv('HOME') . '/.ssh/id_ed25519');
        $this->serverMine = new ServerMine($server->hostname, $server->username, getenv('HOME') . '/.ssh/id_ed25519');
    }
    public function hello(): void
    {
        $this->reply('Привет это первый бот на laravel ');
    }

    public function help(): void
    {
        $this->reply('*Привет!*');
    }




    public function actions(): void
    {
        Telegraph::chat($this->chat)->message('Выбери какое-то действие')->keyboard(
            Keyboard::make()->buttons([
                Button::make('Нагрузка CPU')->action('cpuUsage'),
                Button::make('Нагрузка RAM')->action('ramUsage'),
                Button::make('Места на диске')->action('hddUsage'),
//                Button::make('Подписаться')
//                    ->action('subscribe')
//                    ->param('channel_name', '@fsdfsd'),
            ])
        )->send();
    }

    public function cpuUsage(): void
    {
        $cpuData = $this->systemStats->getCpuUsage();  // Сбор данных
//        $this->reply("Использование CPU: $cpuData%");
        Telegraph::chat($this->chat)->message("Использование CPU: $cpuData%")->send();
    }

    public function serverStartMine()
    {
        $server = $this->serverMine->startServer(); // Старт сервера по майну
        Telegraph::chat($this->chat)->message("Сервер по майну запущен!!!")->send();
    }

    public function serverStopMine()
    {
        $server = $this->serverMine->stopServer(); // Закрыть сервера по майну
        Telegraph::chat($this->chat)->message("Сервер по майну закрыт")->send();
    }




    public function ramUsage(): void
    {
        $ramData = $this->systemStats->getRamUsage();  // Сбор данных RAM
//        $this->reply("Использование RAM: $ramData%");
        Telegraph::chat($this->chat)->message("Использование RAM: $ramData%")->send();

    }

    public function hddUsage(): void
    {
        $hddData = $this->systemStats->getHddUsage();  // Сбор данных HDD
        $this->reply("Использование диска: $hddData");
        Telegraph::chat($this->chat)->message($hddData)->send();

    }


    public function subscribe(): void
    {
        $this->reply("Спасибо за подписку на {$this->data->get('channel_name')}");
    }

    public function like()
    {
        Telegraph::message('Спасибо за лайк')->send();

    }

    protected function handleUnknownCommand(Stringable $text): void
    {
        if ($text->value() === '/start'){
            $this->reply('Этот бот собирает статистику по каждому VDS серверу');
        }else{
            $this->reply('Неизвестная команда');
        }
    }



}
