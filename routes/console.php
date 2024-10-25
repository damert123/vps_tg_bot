<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();


Artisan::command('tester', function () {
   /** @var \DefStudio\Telegraph\Models\TelegraphBot $bot */
    $bot = \DefStudio\Telegraph\Models\TelegraphBot::find(1);
    dd($bot->registerCommands([
        'hello' => 'Говорит привет',
        'help' => 'что умеет этот бот',
        'actions' => 'действия с ботом'
    ])->send());
});
