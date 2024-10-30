<?php

namespace App\Telegram;

use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Net\SSH2;

class SystemStats
{
    protected SSH2 $ssh;

    public function __construct($hostname, $username, $privateKeyPath)
    {
        $this->ssh = new SSH2($hostname);
        $key = PublicKeyLoader::load(file_get_contents($privateKeyPath));

        if (!$this->ssh->login($username, $key)){
            throw new \Exception("Не удалось подключиться к серверу {$hostname} через SSH");
        }
    }

    public function getHddUsage()
    {
        $output = $this->ssh->exec("df -h --total | grep 'total'");
        $disk = preg_split('/\s+/', $output);
        $totalDisk = $disk[1];   // Всего места на диске
        $usedDisk = $disk[2];    // Использовано
        $availableDisk = $disk[3];  // Доступно
        return "Всего: $totalDisk, Занято: $usedDisk, Доступно: $availableDisk";
    }

    public function getRamUsage()
    {
        $output = $this->ssh->exec("free -m");
        $lines = explode("\n", trim($output));
        $memory = preg_split('/\s+/', $lines[1]);
        $totalRam = $memory[1];  // Всего памяти
        $usedRam = $memory[2];   // Занято
        $ramUsage = ($usedRam / $totalRam) * 100;
        return number_format($ramUsage, 2);  // Округление до 2 знаков
    }

    public function getCpuUsage()
    {
        $output = $this->ssh->exec("top -bn1 | grep 'Cpu(s)'");
        // Обработка данных (парсинг строки)
        preg_match('/(\d+[\.,]\d+)\s+id/', $output, $matches);
        $cpuIdle = floatval($matches[0] ?? 0);  // Процент простоя
        $cpuUsage = 100 - $cpuIdle;  // Занятость CPU
        return number_format($cpuUsage, 2);
    }

}
