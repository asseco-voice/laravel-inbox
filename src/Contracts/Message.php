<?php

declare(strict_types=1);

namespace Asseco\Inbox\Contracts;

interface Message
{
    public function isValid(): bool;

    /**
     * Given the parameter to match by, return adequate array of values.
     *
     * Examples:
     *      matching by 'subject' should for email return email subject
     *      matching by 'message' should for SMS return SMS message
     *      matching by 'to' should for email return all recipients
     *
     * @param string $matchBy
     * @return array
     */
    public function getMatchedValues(string $matchBy): array;
}
