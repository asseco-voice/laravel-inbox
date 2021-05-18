<?php

declare(strict_types=1);

namespace Asseco\Inbox\Tests\Feature;

use Asseco\Inbox\Contracts\CanMatch;
use Asseco\Inbox\Facades\InboxGroup;
use Asseco\Inbox\Inbox;
use Asseco\Inbox\Tests\ReplyMail;
use Asseco\Inbox\Tests\TestCase;
use Asseco\Inbox\Tests\TestMail;
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
        $inbox = (new Inbox())
            ->from('{.*}@asseco-see.hr')
            ->action(function (CanMatch $email) {
                $this->assertSame($email->from(), 'example@asseco-see.hr');
                $this->assertSame($email->subject(), 'This is a subject');
            });

        InboxGroup::add($inbox);

        Mail::to('someone@asseco-see.hr')->send(new TestMail);
    }

    /** @test */
    public function it_can_use_fallbacks()
    {
        InboxGroup::fallback(function (CanMatch $email) {
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
            ->action(function (CanMatch $email) {
                Mail::fake();

                $email->reply(new ReplyMail);
            });

        InboxGroup::add($inbox);

        Mail::to('someone@asseco-see.hr')->send(new TestMail);

        Mail::assertSent(ReplyMail::class);
    }
}
