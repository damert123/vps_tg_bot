<?php

namespace App\Telegram;

use App\Models\ChatStatus;
use App\Models\Server;
use DefStudio\Telegraph\Facades\Telegraph;
use DefStudio\Telegraph\Handlers\WebhookHandler;
use DefStudio\Telegraph\Keyboard\Button;
use DefStudio\Telegraph\Keyboard\Keyboard;
use DefStudio\Telegraph\Models\TelegraphBot;
use DefStudio\Telegraph\Models\TelegraphChat;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Stringable;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Net\SSH2;

class Handler extends WebhookHandler
{

    private ?SystemStats $systemStats = null;

//    private bool $isConnected = false; // флаг для отслеживания подключения



    /**
     * @throws \Exception
     */
    public function __construct() {

        try {
            $server = Server::first();
            if (!$server){
                throw new \Exception("Сервер не найден в базе данных.");
            }
            $this->systemStats = new SystemStats($server->hostname, $server->username, getenv('HOME') . '/.ssh/id_rsa');


        }catch (\Exception $e){
            Log::error("Ошибка при подключении к серверу: " . $e->getMessage());
        }


    }



    public function server_add(): void
    {
        Telegraph::chat($this->chat)->message("Добавьте наш публичный ключ на ваш сервер в файл `~/.ssh/authorized_keys`\nКлюч: ssh-rsa....")->keyboard(
            Keyboard::make()->buttons([
                Button::make('✅ Я добавил')->action('serverAddName'),
            ]))->send();

    }

    public function cancelServerAdd()
    {
        $this->deleteChatStatus();
        Telegraph::chat($this->chat)->message('Добавлене сервера отменено')->send();
        $this->reply('');
    }


    public function serverAddName()
    {
        $this->updateUserStatus('adding_name');
        Telegraph::chat($this->chat)->message('Введите имя сервера')->keyboard(
            Keyboard::make()->buttons([
                Button::make('❌ Отменить добавление сервера')->action('cancelServerAdd'),
            ])
        )->send();
        $this->reply('');

    }

    public function serverStore()
    {
        $this->updateUserStatus('awaiting_credentials');
        Telegraph::chat($this->chat)->message('Введите реквизиты доступа для сервера в формате username@hostname')->keyboard(
            Keyboard::make()->buttons([
                Button::make('❌ Отменить добавление сервера')->action('cancelServerAdd'),
            ])
        )->send();
        $this->reply('');

    }

    protected function updateUserStatus($status): void
    {
        ChatStatus::updateOrCreate(
            ['chat_id' => $this->chat->id],
            ['status' => $status]
        );
    }
    protected function deleteChatStatus(): void
    {
        $chatStatus = ChatStatus::where('chat_id', $this->chat->id)->first();
        $chatStatus->delete();
    }

    protected function getUserStatus(): ?string
    {
        $status = ChatStatus::where('chat_id', $this->chat->id)->first();
        return $status ? $status->status : null;
    }


    protected function handleChatMessage(Stringable $text): void
    {
        $statusChat = $this->getUserStatus();
        if ($statusChat === 'adding_name'){
            ChatStatus::updateOrCreate(
                ['chat_id' => $this->chat->id],
                ['status' => 'awaiting_credentials', 'server_name' => $text]
            );
            $this->serverStore();
        }

        if ($statusChat !== 'awaiting_credentials'){
            return;
        }
        if (preg_match('/^[\w-]+@[\w.-]+$/', $text)) {
            // Разделяем сообщение на username и hostname
            [$username, $hostname] = explode('@', $text);

            $chatStatus = ChatStatus::where('chat_id', $this->chat->id)->first();
            $serverName = $chatStatus->server_name;

            if ($this->testSSHConnection($username, $hostname)) {
                $this->storeServerCredentials($username, $hostname, $serverName);  // Сохраняем сервер в базе данных
                Telegraph::chat($this->chat)->message('✅ *Сервер успешно добавлен!*')->send();
                $this->deleteChatStatus();
            } else {
                Telegraph::chat($this->chat)->message('Не удалось подключиться к серверу. Проверьте данные и попробуйте еще раз.')->keyboard(
                    Keyboard::make()->buttons([
                        Button::make('❌ Отменить добавление сервера')->action('cancelServerAdd'),
                    ])
                )->send();
            }
        } else {
            Telegraph::chat($this->chat)->message('Неверный формат. Введите данные в формате username@hostname')->keyboard(
                Keyboard::make()->buttons([
                    Button::make('❌ Отменить добавление сервера')->action('cancelServerAdd'),
                ])
            )->send();
        }

    }

    protected function testSSHConnection($username, $hostname)
    {
        try {
            // Создаем экземпляр SSH2 и загружаем ключ
            $ssh = new SSH2($hostname);
            $privateKeyPath = getenv('HOME') . '/.ssh/id_rsa';
            $key = PublicKeyLoader::load(file_get_contents($privateKeyPath));

            // Проверяем подключение
            if (!$ssh->login($username, $key)) {
                throw new \Exception("Не удалось подключиться к серверу {$hostname} через SSH");
            }
            return true;  // Успешное подключение
        } catch (\Exception $e) {
            Log::error("Ошибка SSH подключения: " . $e->getMessage());
            return false;  // Подключение не удалось
        }
    }

    protected function storeServerCredentials($username, $hostname, $serverName)
    {
        $privateKeyPath = getenv('HOME') . '/.ssh/id_rsa'; // Путь к вашему приватному ключу
        $publicKeyPath = $privateKeyPath . '.pub';

        Server::create([
            'username' => $username,
            'ssh_key' => $publicKeyPath,
            'server_name' => $serverName,
            'hostname' => $hostname,
        ]);
    }


    public function server_list(): void
    {
//        if (!$this->isConnected){
//            $this->reply("Ошибка не удалось подключиться к VPS серверу.");
//            return;
//        }
        $servers = Server::all();

        if ($servers->isEmpty()) {
            $this->reply("*Список серверов пуст!*\nВоспользуйтесь командой\n`/server_add`");
            return;
        }

        $buttons = [];

        foreach ($servers as $server){
            $buttons[] = Button::make('💻 VPS ' . $server->server_name . ' ' . $server->hostname )
                ->action('serverStat')->param('hostname', $server->hostname);
        }


        Telegraph::chat($this->chat)->message('Выбери какое-то действие')->keyboard(
            Keyboard::make()->buttons($buttons)
        )->send();


//        Telegraph::chat($this->chat)->message('Выбери какое-то действие')->keyboard(
//            Keyboard::make()->buttons([
//                Button::make('💻 VPS сервер ' . Server::pluck('hostname')->first())->action('serverStat'),
//            ])
//        )->send();
    }


    public function server_monitoring()
    {
//        if (!$this->isConnected) {
//            $this->reply("Ошибка: не удалось подключиться к VPS серверу.");
//            return;
//        }

        $servers = Server::all();

        if ($servers->isEmpty()) {
            $this->reply("*Вы не добавили сервер!*\nВоспользуйтесь командой\n`/server_add`");
            return;
        }

        $message = "Статистика серверов:\n\n";

        foreach ($servers as $server){
            $serverStats = $server->monitorings()->latest()->first();

            if (!$serverStats){
                $message .= "Сервер {$server->hostname}: нет доступных данных для статистики, подождите минуту\n\n";
                continue;
            }

            if ($server->monitorings->ssh_connection == 'error'){
                $message .= "⚠️ Ошибка: VPS *{$server->server_name}*: {$server->monitorings->error_message}\n\n ";
            }else{
                $message .= "*{$server->server_name}* {$server->hostname} \n";
                $message .= " ⚙️ Использование CPU: {$serverStats->last_cpu_usage}%\n";
                $message .= " 💾 Использование RAM: {$serverStats->last_ram_usage}%\n";
                $message .= " 💿 Места на диске: {$serverStats->last_hdd_usage}\n";
                $message .= " 📅 Последнее обновление: {$serverStats->last_update}\n\n";
            }



        }

        Telegraph::chat($this->chat)->message($message)->send();
        $this->reply('');

    }

    public function serverStat($hostname)
    {
//        if (!$this->isConnected) {
//            $this->reply("Ошибка: не удалось подключиться к VPS серверу.");
//            return;
//        }

        $server = Server::where('hostname', $hostname)->first();

        if (!$server){
            $this->reply('⚠️ Ошибка: сервер не найден');
            return;
        }


        $serverStats = $server->monitorings; // Через отношение модели Server

        if (!$serverStats){
            Telegraph::chat($this->chat)->message("Сервер {$server->hostname}: нет доступных данных для статистики, подождите минуту")->send();
            $this->reply('');
            return;

        }


        if ($server->monitorings->ssh_connection == 'error'){
            $message = "⚠️ Ошибка: VPS *{$server->server_name}*: {$server->monitorings->error_message}\n\n ";
        }else{
            $message = "Статистика сервера: \n";
            $message .= " ⚙️ Использование CPU: {$serverStats->last_cpu_usage}%\n";
            $message .= " 💾 Использование RAM: {$serverStats->last_ram_usage}%\n";
            $message .= " 💿 Места на диске: {$serverStats->last_hdd_usage}\n";
            $message .= " 📅 Последнее обновление: {$serverStats->last_update}";
        }

        Telegraph::chat($this->chat)->message($message)->send();
        $this->reply('');


    }


    protected function handleUnknownCommand(Stringable $text): void
    {
        if ($text->value() === '/start'){
            $this->reply('Этот бот собирает статистику по каждому VPS серверу');
        }else{
            $this->reply('Неизвестная команда');
        }
    }



}
