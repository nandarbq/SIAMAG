<?php

namespace App\Providers;

use Illuminate\Foundation\Console\ServeCommand;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (PHP_OS_FAMILY !== 'Windows' || ! $this->app->runningInConsole()) {
            return;
        }

        $tempDirectory = $_ENV['TMP'] ?? getenv('TMP');
        $tempDirectory = $tempDirectory ?: sys_get_temp_dir();

        $_ENV['TMP'] = $tempDirectory;
        $_ENV['TEMP'] = $tempDirectory;

        if (! in_array('TMP', ServeCommand::$passthroughVariables, true)) {
            ServeCommand::$passthroughVariables[] = 'TMP';
        }

        if (! in_array('TEMP', ServeCommand::$passthroughVariables, true)) {
            ServeCommand::$passthroughVariables[] = 'TEMP';
        }
    }
}
