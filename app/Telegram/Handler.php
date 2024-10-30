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

    private bool $isConnected = false; // флаг для отслеживания подключения



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
            $this->isConnected = true;

        }catch (\Exception $e){
            $this->isConnected = false;
            Log::error("Ошибка при подключении к серверу: " . $e->getMessage());
        }


    }
    public function hello(): void
    {
        $this->reply('Привет это первый бот на laravel ');
    }

    public function help(): void
    {
        $this->reply('*Привет!*');
    }


    public function server_add(): void
    {
        Telegraph::chat($this->chat)->message("Добавьте наш публичный ключ на ваш сервер в файл `~/.ssh/authorized_keys`\n Ключ: ssh-rsa....")->keyboard(
            Keyboard::make()->buttons([
                Button::make('✅ Я добавил')->action('serverStore'),
            ]))->send();

    }

    public function cancelServerAdd()
    {
        $this->updateUserStatus(null);
        Telegraph::chat($this->chat)->message('Добавлене сервера отменено')->send();
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

    protected function getUserStatus(): ?string
    {
        $status = ChatStatus::where('chat_id', $this->chat->id)->first();
        return $status ? $status->status : null;
    }


    protected function handleChatMessage(Stringable $text): void
    {
        if ($this->getUserStatus() !== 'awaiting_credentials'){
            return;
        }
        if (preg_match('/^[\w-]+@[\w.-]+$/', $text)) {
            // Разделяем сообщение на username и hostname
            [$username, $hostname] = explode('@', $text);

            if ($this->testSSHConnection($username, $hostname)) {
                $this->storeServerCredentials($username, $hostname);  // Сохраняем сервер в базе данных
                Telegraph::chat($this->chat)->message('Сервер успешно добавлен!')->send();
                $this->updateUserStatus(null);
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

    protected function storeServerCredentials($username, $hostname)
    {
        $privateKeyPath = getenv('HOME') . '/.ssh/id_rsa'; // Путь к вашему приватному ключу
        $publicKeyPath = $privateKeyPath . '.pub';

        Server::create([
            'username' => $username,
            'ssh_key' => $publicKeyPath,
            'hostname' => $hostname,
        ]);
    }


    public function server_list(): void
    {
        if (!$this->isConnected){
            $this->reply("Ошибка не удалось подключиться к VPS серверу.");
            return;
        }
        $servers = Server::pluck('hostname');

        if ($servers->isEmpty()) {
            $this->reply("Ошибка: не найдены сервера.");
            return;
        }

        $buttons = [];

        foreach ($servers as $hostname){
            $buttons[] = Button::make('💻 VPS сервер ' . $hostname )
                ->action('serverStat')->param('hostname', $hostname);
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
        if (!$this->isConnected) {
            $this->reply("Ошибка: не удалось подключиться к VPS серверу.");
            return;
        }

        $servers = Server::all();

        if ($servers->isEmpty()) {
            $this->reply("Ошибка: не найдены сервера.");
            return;
        }

        $message = "Статистика серверов:\n\n";

        foreach ($servers as $server){
            $serverStats = $server->monitorings()->latest()->first();

            if (!$serverStats){
                $message .= "Сервер {$server->hostname}: нет доступных данных для статистикиюю\n";
                continue;
            }

            $message .= "Сервер: {$server->hostname} \n";
            $message .= " ⚙️ Использование CPU: {$serverStats->last_cpu_usage}%\n";
            $message .= " 💾 Использование RAM: {$serverStats->last_ram_usage}%\n";
            $message .= " 💿 Места на диске: {$serverStats->last_hdd_usage}\n";
            $message .= " 📅 Последнее обновление: {$serverStats->last_update}\n\n";

        }

        Telegraph::chat($this->chat)->message($message)->send();
        $this->reply('');

    }

    public function serverStat($hostname)
    {
        if (!$this->isConnected) {
            $this->reply("Ошибка: не удалось подключиться к VPS серверу.");
            return;
        }

        $server = Server::where('hostname', $hostname)->first();

        if (!$server){
            $this->reply('Ошибка: сервер не найден');
            return;
        }

        $serverStats = $server->monitorings; // Через отношение модели Server

        if (!$serverStats){
            $this->reply("Ошибка: не найдены данные для сервера.");
        }

        $message = "Статистика сервера: \n";
        $message .= " ⚙️ Использование CPU: {$serverStats->last_cpu_usage}%\n";
        $message .= " 💾 Использование RAM: {$serverStats->last_ram_usage}%\n";
        $message .= " 💿 Места на диске: {$serverStats->last_hdd_usage}\n";
        $message .= " 📅 Последнее обновление: {$serverStats->last_update}";

        Telegraph::chat($this->chat)->message($message)->send();
        $this->reply('');




    }




    public function subscribe(): void
    {
        $this->reply("Спасибо за подписку на {$this->data->get('channel_name')}");
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
