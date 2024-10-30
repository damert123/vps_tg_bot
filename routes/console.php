<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();


Artisan::command('tester', function () {
   /** @var \DefStudio\Telegraph\Models\TelegraphBot $bot */
    $bot = \DefStudio\Telegraph\Models\TelegraphBot::first();
    dd($bot->registerCommands([
        'server_add' => 'Добавить сервер',
        'server_list' => 'Список серверов',
        'server_monitoring' => 'Статистика серверов',
    ])->send());
});


//Artisan::command('monitor', function () {
//    $this->info('Мониторинг сервера выполняется');
//
//})->purpose('Статистика');
//
//Schedule::command('monitor')->everyMinute();

//Schedule::command('testInsert')->everyMinute();

//Artisan::command('monitor', function (){
//    /** @var \DefStudio\Telegraph\Models\TelegraphBot $bot */
//    /** @var \DefStudio\Telegraph\Models\TelegraphChat $chat */
//
//    $bot = \DefStudio\Telegraph\Models\TelegraphBot::find(1);
//    \DefStudio\Telegraph\Facades\Telegraph::chat(\DefStudio\Telegraph\Models\TelegraphChat::find(1))->message('Привет я пишусь раз в минуту УРА!')->send();
//
//});


Schedule::command('monitor')->everyMinute();


