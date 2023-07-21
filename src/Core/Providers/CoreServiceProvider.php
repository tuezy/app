<?php
namespace Tuezy\Core\Providers;

use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\ServiceProvider;
use Tuezy\Core\Core;
use Tuezy\Core\Facades\Core as CoreFacade;

class CoreServiceProvider extends ServiceProvider{
    public function boot(){
        include __DIR__ . '/../helper.php';

        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
    }

    public function register(){
        $loader = AliasLoader::getInstance();
        $loader->alias('core', CoreFacade::class);

        $this->app->singleton('core', function () {
            return app()->make(Core::class);
        });
    }
}