<?php

declare(strict_types=1);

namespace Asseco\Mailbox\Facades;

use Asseco\Mailbox\InboundEmail;
use Asseco\Mailbox\Routing\Mailbox;
use Illuminate\Support\Facades\Facade;

/**
 * @method static void run(InboundEmail $email)
 * @method static self add(Mailbox $mailbox)
 * @method static self fallback($action)
 * @method static self continuousMatching()
 *
 * @see \BeyondCode\Mailbox\Routing\MailboxGroup
 */
class MailboxGroup extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'mailbox-group';
    }
}
