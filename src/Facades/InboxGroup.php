<?php

declare(strict_types=1);

namespace Asseco\Inbox\Facades;

use Asseco\Inbox\Contracts\CanMatch;
use Asseco\Inbox\Inbox;
use Illuminate\Support\Facades\Facade;

/**
 * @method static void run(CanMatch $message)
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
