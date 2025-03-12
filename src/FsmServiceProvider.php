<?php

namespace Rapid\Fsm;

use Illuminate\Support\ServiceProvider;
use Rapid\Fsm\Configuration\ContextConfiguration;
use Rapid\Fsm\Configuration\DefaultContextConfiguration;
use Rapid\Fsm\Logging\EmptyLogger;
use Rapid\Fsm\Logging\Logger;

class FsmServiceProvider extends ServiceProvider
{

    protected array $commands = [
    ];

    public function register()
    {
        $this->registerConfig();
        $this->registerLang();
        $this->commands($this->commands);

        $this->app->bindIf(ContextConfiguration::class, DefaultContextConfiguration::class);
        $this->app->bindIf(Logger::class, EmptyLogger::class);
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