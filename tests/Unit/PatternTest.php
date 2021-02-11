<?php

namespace Asseco\Inbox\Tests\Unit;

use Asseco\Inbox\Facades\InboxGroup;
use Asseco\Inbox\InboundEmail;
use Asseco\Inbox\Routing\Inbox;
use Asseco\Inbox\Tests\TestCase;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;

class PatternTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $this->catchLocalEmails();
    }

    /** @test */
    public function it_matches_from_pattern()
    {
        $inbox = (new Inbox())
            ->to('{pattern}@beyondco.de')
            ->where('pattern', '.*')
            ->action(function ($email) {
            });

        InboxGroup::add($inbox);

        Mail::to('someone@beyondco.de')->send(new PatternTestMail);
        Mail::to('someone-else@beyondco.de')->send(new PatternTestMail);

        $this->assertSame(2, InboundEmail::query()->count());
    }

    /** @test */
    public function it_rejects_wrong_pattern()
    {
        $inbox = (new Inbox())
            ->to('{pattern}@beyondco.de')
            ->where('pattern', '[a-z]+')
            ->action(function ($email) {
            });

        InboxGroup::add($inbox);

        Mail::to('123@beyondco.de')->send(new PatternTestMail);
        Mail::to('456@beyondco.de')->send(new PatternTestMail);

        $this->assertSame(0, InboundEmail::query()->count());
    }

    /** @test */
    public function it_matches_multiple_one_line_patterns()
    {
        $inbox = (new Inbox())
            ->to('{username}@{provider}')
            ->where('username', '[a-z]+')
            ->where('provider', 'beyondco.de')
            ->action(function ($email) {
            });

        InboxGroup::add($inbox);

        Mail::to('someone@beyondco.de')->send(new PatternTestMail);
        Mail::to('someone-else@gmail.com')->send(new PatternTestMail);

        $this->assertSame(1, InboundEmail::query()->count());
    }

    /** @test */
    public function it_matches_multiple_patterns()
    {
        $inbox = (new Inbox())
            ->from('{pattern}@beyondco.de')
            ->to('{pattern}@beyondco.de')
            ->where('pattern', '[a-z]+')
            ->action(function ($email) {
            });

        InboxGroup::add($inbox);

        Mail::to('someone@beyondco.de')->send(new PatternTestMail);
        Mail::to('someone-else@beyondco.de')->send(new PatternTestMail);

        $this->assertSame(1, InboundEmail::query()->count());
    }

    /** @test */
    public function it_matches_at_least_one_pattern()
    {
        $inbox = (new Inbox())
            ->from('{pattern}@beyondco.de')
            ->to('someone@{provider}.com')
            ->where('pattern', '[a-z]+')
            ->where('provider', 'gmail')
            ->matchEither()
            ->action(function ($email) {
            });

        InboxGroup::add($inbox);

        Mail::to('someone@beyondco.de')->send(new PatternTestMail);
        Mail::to('someone-else@gmail.com')->send(new PatternTestMail);

        $this->assertSame(2, InboundEmail::query()->count());
    }
}

class PatternTestMail extends Mailable
{
    public function build()
    {
        $this->from('example@beyondco.de')
            ->subject('This is a subject')
            ->html('<html>Example email content</html>');
    }
}
