<?php

declare(strict_types=1);

namespace Asseco\Inbox\Tests\Feature;

use Asseco\Inbox\Facades\InboxGroup;
use Asseco\Inbox\InboundEmail;
use Asseco\Inbox\Inbox;
use Asseco\Inbox\Tests\TestCase;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;

class InboundEmailTest extends TestCase
{
    protected Inbox $inbox;

    public function setUp(): void
    {
        parent::setUp();

        $this->inbox = new Inbox();
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
