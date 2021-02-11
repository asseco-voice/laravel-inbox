<?php

declare(strict_types=1);

namespace Asseco\Inbox\Facades;

use Asseco\Inbox\InboundEmail;
use Asseco\Inbox\Inbox;
use Illuminate\Support\Facades\Facade;

/**
 * @method static void run(InboundEmail $email)
 * @method static self add(Inbox $inbox)
 * @method static self fallback($action)
 * @method static self continuousMatching()
 *
 * @see \Asseco\Inbox\InboxGroup
 */
class InboxGroup extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'inbox-group';
    }
}
