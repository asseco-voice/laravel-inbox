<?php

namespace Asseco\Inbox\Tests\Feature;

use Asseco\Inbox\Facades\InboxGroup;
use Asseco\Inbox\InboundEmail;
use Asseco\Inbox\Inbox;
use Asseco\Inbox\Tests\TestCase;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;

class InboundEmailTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $this->catchLocalEmails();
    }

    public function processLog(MessageSent $event)
    {
        /** @var InboundEmail $modelClass */
        $modelClass = config('mailbox.model');
        $email = $modelClass::fromMessage($event->message);

        InboxGroup::run($email);
    }

    /** @test */
    public function it_catches_logged_mails()
    {
        $inbox = (new Inbox())->from('{name}@asseco-see.hr')->action(function (InboundEmail $email, $name) {
            $this->assertSame($name, 'example');
            $this->assertSame($email->from(), 'example@asseco-see.hr');
            $this->assertSame($email->subject(), 'This is a subject');
        });

        InboxGroup::add($inbox);

        Mail::to('someone@asseco-see.hr')->send(new TestMail);
    }

    /** @test */
    public function it_stores_inbound_emails()
    {
        $inbox = (new Inbox())
            ->to('someone@asseco-see.hr')
            ->action(function ($email) {
            });

        InboxGroup::add($inbox);

        Mail::to('someone@asseco-see.hr')->send(new TestMail);
        Mail::to('someone-else@asseco-see.hr')->send(new TestMail);

        $this->assertSame(1, InboundEmail::query()->count());
    }

    /** @test */
    public function it_stores_all_inbound_emails()
    {
        config()->set('mailbox.only_store_matching_emails', false);

        $inbox = (new Inbox())
            ->to('someone@asseco-see.hr')
            ->action(function ($email) {
            });

        InboxGroup::add($inbox);

        Mail::to('someone@asseco-see.hr')->send(new TestMail);
        Mail::to('someone-else@asseco-see.hr')->send(new TestMail);

        $this->assertSame(2, InboundEmail::query()->count());
    }

    /** @test */
    public function it_can_use_fallbacks()
    {
        InboxGroup::fallback(function (InboundEmail $email) {
            Mail::fake();

            $email->reply(new ReplyMail);
        });

        Mail::to('someone@asseco-see.hr')->send(new TestMail);

        Mail::assertSent(ReplyMail::class);
    }

    /** @test */
    public function it_stores_inbound_emails_with_fallback()
    {
        InboxGroup::fallback(function ($email) {
        });

        Mail::to('someone@asseco-see.hr')->send(new TestMail);
        Mail::to('someone-else@asseco-see.hr')->send(new TestMail);

        $this->assertSame(2, InboundEmail::query()->count());
    }

    /** @test */
    public function it_does_not_store_inbound_emails_if_configured()
    {
        $this->app['config']['mailbox.store_incoming_emails_for_days'] = 0;

        $inbox = (new Inbox())
            ->from('example@asseco-see.hr')
            ->action(function ($email) {
            });

        InboxGroup::add($inbox);

        Mail::to('someone@asseco-see.hr')->send(new TestMail);
        Mail::to('someone@asseco-see.hr')->send(new TestMail);

        $this->assertSame(0, InboundEmail::query()->count());
    }

    /** @test */
    public function it_can_reply_to_mails()
    {
        $inbox = (new Inbox())
            ->from('example@asseco-see.hr')
            ->action(function (InboundEmail $email) {
                Mail::fake();

                $email->reply(new ReplyMail);
            });

        InboxGroup::add($inbox);

        Mail::to('someone@asseco-see.hr')->send(new TestMail);

        Mail::assertSent(ReplyMail::class);
    }

    /** @test */
    public function it_uses_the_configured_model()
    {
        $this->app['config']['mailbox.model'] = ExtendedInboundEmail::class;

        $inbox = (new Inbox())->from('example@asseco-see.hr')->action(function ($email) {
            $this->assertInstanceOf(ExtendedInboundEmail::class, $email);
        });

        InboxGroup::add($inbox);

        Mail::to('someone@asseco-see.hr')->send(new TestMail);
    }
}

class TestMail extends Mailable
{
    public function build()
    {
        $this->from('example@asseco-see.hr')
            ->subject('This is a subject')
            ->html('<html>Example email content</html>');
    }
}

class ReplyMail extends Mailable
{
    public function build()
    {
        $this->from('marcel@asseco-see.hr')
            ->subject('This is my reply')
            ->html('Hi!');
    }
}

class ExtendedInboundEmail extends InboundEmail
{
}
