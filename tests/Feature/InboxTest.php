<?php

declare(strict_types=1);

namespace Asseco\Inbox\Tests\Feature;

use Asseco\Inbox\Contracts\CanMatch;
use Asseco\Inbox\Facades\InboxGroup;
use Asseco\Inbox\Inbox;
use Asseco\Inbox\Tests\TestCase;
use Asseco\Inbox\Tests\TestMail;
use Illuminate\Support\Facades\Mail;

class InboxTest extends TestCase
{
    protected Inbox $inbox;

    public function setUp(): void
    {
        parent::setUp();

        $this->inbox = new Inbox();
    }

    /** @test */
    public function it_matches_from_pattern()
    {
        $this->inbox
            ->to('{.*}@asseco-see.hr')
            ->action(function (CanMatch $email) {
                $this->assertEquals(['someone@asseco-see.hr'], $email->to());
            });

        InboxGroup::add($this->inbox);

        Mail::to('someone@asseco-see.hr')->send(new TestMail);
    }

    /** @test */
    public function it_rejects_wrong_pattern()
    {
        $this->inbox
            ->to('{[a-z]+}@asseco-see.hr')
            ->action(function ($email) {
            });

        InboxGroup::add($this->inbox);

        Mail::to('123@asseco-see.hr')->send(new TestMail);
        Mail::to('456@asseco-see.hr')->send(new TestMail);

        $this->assertTrue(true);
    }

    /** @test */
    public function it_matches_multiple_one_line_patterns()
    {
        $this->inbox
            ->to('{[a-z]+}@{asseco-see.hr}')
            ->action(function ($email) {
                $this->assertEquals(['someone@asseco-see.hr'], $email->to());
            });

        InboxGroup::add($this->inbox);

        Mail::to('someone@asseco-see.hr')->send(new TestMail);
    }

    /** @test */
    public function it_matches_multiple_patterns()
    {
        $this->inbox
            ->from('{[a-z]+}@asseco-see.hr')
            ->to('{[a-z]+}@asseco-see.hr')
            ->action(function ($email) {
                $this->assertEquals(['someone@asseco-see.hr'], $email->to());
            });

        InboxGroup::add($this->inbox);

        Mail::to('someone@asseco-see.hr')->send(new TestMail);
    }

    /** @test */
    public function it_matches_at_least_one_pattern()
    {
        $this->inbox
            ->from('{[a-z]+}@asseco-see.hr')
            ->to('someone@{gmail}.com')
            ->matchEither()
            ->action(function ($email) {
                $this->assertEquals(['someone@asseco-see.hr'], $email->to());
            });

        InboxGroup::add($this->inbox);

        Mail::to('someone@asseco-see.hr')->send(new TestMail);
    }
}
