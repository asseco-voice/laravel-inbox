<?php

namespace Asseco\Inbox\Tests\Feature;

use Asseco\Inbox\Facades\InboxGroup;
use Asseco\Inbox\InboundEmail;
use Asseco\Inbox\Inbox;
use Asseco\Inbox\Tests\TestCase;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;

class PatternTest extends TestCase
{
    /** @test */
    public function it_matches_from_pattern()
    {
        $inbox = (new Inbox())
            ->to('{pattern}@asseco-see.hr')
            ->where('pattern', '.*')
            ->action(function ($email) {
            });

        InboxGroup::add($inbox);

        Mail::to('someone@asseco-see.hr')->send(new PatternTestMail);
        Mail::to('someone-else@asseco-see.hr')->send(new PatternTestMail);

        $this->assertSame(2, InboundEmail::query()->count());
    }

    /** @test */
    public function it_rejects_wrong_pattern()
    {
        $inbox = (new Inbox())
            ->to('{pattern}@asseco-see.hr')
            ->where('pattern', '[a-z]+')
            ->action(function ($email) {
            });

        InboxGroup::add($inbox);

        Mail::to('123@asseco-see.hr')->send(new PatternTestMail);
        Mail::to('456@asseco-see.hr')->send(new PatternTestMail);

        $this->assertSame(0, InboundEmail::query()->count());
    }

    /** @test */
    public function it_matches_multiple_one_line_patterns()
    {
        $inbox = (new Inbox())
            ->to('{username}@{provider}')
            ->where('username', '[a-z]+')
            ->where('provider', 'asseco-see.hr')
            ->action(function ($email) {
            });

        InboxGroup::add($inbox);

        Mail::to('someone@asseco-see.hr')->send(new PatternTestMail);
        Mail::to('someone-else@gmail.com')->send(new PatternTestMail);

        $this->assertSame(1, InboundEmail::query()->count());
    }

    /** @test */
    public function it_matches_multiple_patterns()
    {
        $inbox = (new Inbox())
            ->from('{pattern}@asseco-see.hr')
            ->to('{pattern}@asseco-see.hr')
            ->where('pattern', '[a-z]+')
            ->action(function ($email) {
            });

        InboxGroup::add($inbox);

        Mail::to('someone@asseco-see.hr')->send(new PatternTestMail);
        Mail::to('someone-else@asseco-see.hr')->send(new PatternTestMail);

        $this->assertSame(1, InboundEmail::query()->count());
    }

    /** @test */
    public function it_matches_at_least_one_pattern()
    {
        $inbox = (new Inbox())
            ->from('{pattern}@asseco-see.hr')
            ->to('someone@{provider}.com')
            ->where('pattern', '[a-z]+')
            ->where('provider', 'gmail')
            ->matchEither()
            ->action(function ($email) {
            });

        InboxGroup::add($inbox);

        Mail::to('someone@asseco-see.hr')->send(new PatternTestMail);
        Mail::to('someone-else@gmail.com')->send(new PatternTestMail);

        $this->assertSame(2, InboundEmail::query()->count());
    }
}

class PatternTestMail extends Mailable
{
    public function build()
    {
        $this->from('example@asseco-see.hr')
            ->subject('This is a subject')
            ->html('<html>Example email content</html>');
    }
}
