<?php

declare(strict_types=1);

namespace Asseco\Mailbox\Routing;

use Asseco\Mailbox\InboundEmail;
use Exception;

class MailboxGroup
{
    protected array $mailboxes = [];

    protected bool $continuousMatching = false;

    protected ?Mailbox $fallback = null;

    public function add(Mailbox $mailbox): self
    {
        $this->mailboxes[] = $mailbox;

        return $this;
    }

    /**
     * @param InboundEmail $email
     * @throws Exception
     */
    public function run(InboundEmail $email): void
    {
        $matchedAny = false;
        $mailboxes = collect($this->mailboxes)->sortByDesc('priority');

        /**
         * @var $mailbox Mailbox
         */
        foreach ($mailboxes as $mailbox) {
            $matched = $mailbox->run($email);

            if (!$matched) {
                continue;
            }

            if (!$this->continuousMatching) {
                break;
            }
        }

        if (!$matchedAny && $this->fallback !== null) {
            $this->fallback->run($email);
        }
    }

    public function fallback($action): self
    {
        /**
         * @var Mailbox $mailbox
         */
        $mailbox = app(Mailbox::class);
        $mailbox->action($action);

        $this->fallback = $mailbox;

        return $this;
    }

    public function continuousMatching(): self
    {
        $this->continuousMatching = true;

        return $this;
    }
}
