<?php

namespace App\Console\Commands;

use App\Models\Server;
use Illuminate\Console\Command;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Crypt\RSA;
use phpseclib3\Net\SSH2;

class CreateServerCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'server:register {hostname} {username}';

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
        $hostname = $this->argument('hostname');
        $username = $this->argument('username');
        $privateKeyPath = $_SERVER['HOME'] . '/.ssh/id_ed25519'; // Путь к вашему приватному ключу
        $publicKeyPath = $privateKeyPath . '.pub'; // Путь к публичному ключу


        if (Server::where('hostname', $hostname)->first()){
            $this->error("Сервер {$hostname} уже используется");
            return;
        }

        // Загружаем приватный ключ
        $key = PublicKeyLoader::load(file_get_contents($privateKeyPath));


        // Подключаемся к серверу через SSH
        $ssh = new SSH2($hostname);
        if (!$ssh->login($username, $key)) {
            $this->error("Не удалось подключиться к серверу {$hostname} через SSH");
            return;
        }

        $publicKey = file_get_contents($publicKeyPath);

        // Если подключение успешно, сохраняем сервер в базе данных
        Server::create([
            'hostname' => $hostname,
            'username' => $username,
            'ssh_key' => $publicKey, // Здесь можно хранить путь к публичному ключу
        ]);

        $this->info("Сервер {$hostname} успешно зарегистрирован");
    }
}
