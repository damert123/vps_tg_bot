<?php

namespace App\Telegram;

use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Net\SSH2;

class ServerMine
{
    protected SSH2 $ssh;

    public function __construct($hostname, $username, $privateKeyPath)
    {
        $this->ssh = new SSH2($hostname);
        $key = PublicKeyLoader::load(file_get_contents($privateKeyPath));

        if (!$this->ssh->login($username, $key)){
            throw new \Exception('Не удалось подключиться к серверу {$hostname} через SSH');
        }
    }

    public function startServer()
    {
        $output = $this->ssh->exec("cd minecraft && screen -dmS minecraft_server java -Xmx3G -jar server.jar -nogui");
        sleep(5);
        return 'Успех !!!';

    }

    public function stopServer()
    {
        $output = $this->ssh->exec("cd minecraft && screen -S minecraft_server -X stuff \"stop\n\"");
        return 'Успех !!!';

    }




}