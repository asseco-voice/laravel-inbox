<?php

declare(strict_types=1);

namespace Asseco\Inbox;

use Exception;

class Pattern
{
    const FROM = 'from';
    const TO = 'to';
    const CC = 'cc';
    const BCC = 'bcc';
    const SUBJECT = 'subject';

    public string $matchBy;
    public string $regex;

    /**
     * Pattern constructor.
     *
     * @param  string  $matchBy
     * @param  string  $regex
     *
     * @throws Exception
     */
    public function __construct(string $matchBy, string $regex)
    {
        $this->matchBy = $matchBy;
        $this->regex = $regex;
    }
}
