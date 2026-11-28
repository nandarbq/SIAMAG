<?php

namespace App\Providers;

use App\Http\Middleware\EnsureUserIsActive;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Console\ServeCommand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Livewire\Livewire;

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
        Livewire::addPersistentMiddleware(EnsureUserIsActive::class);

        RateLimiter::for('login', function (Request $request) {
            $email = Str::lower((string) $request->input('email'));

            return [
                Limit::perMinute(5)->by($email.'|'.$request->ip()),
                Limit::perMinute(20)->by($request->ip()),
            ];
        });

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
