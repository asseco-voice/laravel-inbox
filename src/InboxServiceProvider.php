<?php

declare(strict_types=1);

namespace Asseco\Mailbox;

use Asseco\Mailbox\Http\Middleware\MailboxBasicAuthentication;
use Asseco\Mailbox\Routing\Mailbox;
use Asseco\Mailbox\Routing\MailboxGroup;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class InboxServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        if (! class_exists('CreateMailboxInboundEmailsTable')) {
            $this->publishes([
                __DIR__.'/../database/migrations/create_mailbox_inbound_emails_table.php.stub' => database_path('migrations/'.date('Y_m_d_His', time()).'_create_mailbox_inbound_emails_table.php'),
            ], 'migrations');
        }

        $this->publishes([
            __DIR__.'/../config/mailbox.php' => config_path('mailbox.php'),
        ], 'config');

        Route::aliasMiddleware('laravel-mailbox-auth', MailboxBasicAuthentication::class);

        $this->commands([
            Console\CleanEmails::class,
        ]);
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/mailbox.php', 'mailbox');

        $this->app->bind(Mailbox::class);
        $this->app->singleton('mailbox-group', MailboxGroup::class);
    }
}
