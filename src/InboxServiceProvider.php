<?php

declare(strict_types=1);

namespace Asseco\Inbox;

use Illuminate\Support\ServiceProvider;

class InboxServiceProvider extends ServiceProvider
{
    /**
     * Register the application services.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/asseco-inbox.php', 'asseco-inbox');

        $this->app->bind(Inbox::class);
        $this->app->singleton('inbox-group', InboxGroup::class);
    }

    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        $this->publishes([__DIR__ . '/../config/asseco-inbox.php' => config_path('asseco-inbox.php')]);
    }
}
