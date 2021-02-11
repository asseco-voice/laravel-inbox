<?php

declare(strict_types=1);

namespace Asseco\Inbox\Tests\Unit;

use Asseco\Inbox\Pattern;
use Asseco\Inbox\Tests\TestCase;
use Exception;

class PatternTest extends TestCase
{
    /** @test */
    public function accepts_valid_match_parameter()
    {
        $pattern = new Pattern('from', '');

        $this->assertEquals('from', $pattern->matchBy);
    }

    /** @test */
    public function rejects_invalid_match_parameter()
    {
        $this->expectException(Exception::class);

        new Pattern('invalid', '');
    }
}
