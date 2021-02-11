<?php

declare(strict_types=1);

namespace Asseco\Inbox\Tests;

use Asseco\Inbox\Facades\InboxGroup;
use Asseco\Inbox\InboundEmail;
use Asseco\Inbox\InboxServiceProvider;
use Illuminate\Mail\Events\MessageSent;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app)
    {
        return [InboxServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app)
    {
        config(['mail.default' => 'log']);

        $this->catchLocalEmails();
    }

    protected function catchLocalEmails()
    {
        app('events')->listen(MessageSent::class, [$this, 'processLog']);
    }

    public function processLog(MessageSent $event)
    {
        /**
         * @var InboundEmail $modelClass
         */
        $modelClass = config('asseco-inbox.model');
        $email = $modelClass::fromMessage($event->message);

        InboxGroup::run($email);
    }
}
