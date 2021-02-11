<?php

declare(strict_types=1);

namespace Asseco\Mailbox\Routing;

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
     * @param string $matchBy
     * @param string $regex
     * @throws Exception
     */
    public function __construct(string $matchBy, string $regex)
    {
        if (! in_array($matchBy, [self::FROM, self::TO, self::CC, self::BCC, self::SUBJECT])) {
            throw new Exception('Invalid matchBy parameter.');
        }

        $this->matchBy = $matchBy;
        $this->regex = $regex;
    }
}
