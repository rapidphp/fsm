<?php

namespace Rapid\Fsm;

use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\ServiceProvider;

class FsmServiceProvider extends ServiceProvider
{

    protected array $commands = [
    ];

    public function register()
    {
        $this->registerConfig();
        $this->registerLang();
        $this->commands($this->commands);
    }

    protected function registerConfig()
    {
        $config = __DIR__ . '/../config/fsm.php';
        $this->publishes([$config => base_path('config/fsm.php')], ['fsm']);
        $this->mergeConfigFrom($config, 'fsm');
    }

    protected function registerLang()
    {
        $lang = __DIR__ . '/../lang';
        $this->publishes([$lang => $this->app->langPath('vendor/fsm')], ['fsm:lang', 'lang']);
        $this->loadTranslationsFrom($lang, 'fsm');
    }

}