<?php

namespace Asseco\Mailbox\Tests;

use Asseco\Mailbox\Facades\MailboxGroup;
use Asseco\Mailbox\InboundEmail;
use Asseco\Mailbox\InboxServiceProvider;
use Illuminate\Mail\Events\MessageSent;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    protected function getPackageProviders($app)
    {
        return [InboxServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app)
    {
        include_once __DIR__.'/../database/migrations/create_mailbox_inbound_emails_table.php.stub';

        (new \CreateMailboxInboundEmailsTable())->up();
    }

    protected function catchLocalEmails()
    {
        app('events')->listen(MessageSent::class, [$this, 'processLog']);
    }

    public function processLog(MessageSent $event)
    {
        /** @var InboundEmail $modelClass */
        $modelClass = config('mailbox.model');
        $email = $modelClass::fromMessage($event->message);

        MailboxGroup::run($email);
    }
}
