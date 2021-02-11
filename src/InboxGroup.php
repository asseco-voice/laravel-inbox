<?php

declare(strict_types=1);

namespace Asseco\Inbox;

use Exception;

class InboxGroup
{
    protected array $inboxes = [];

    protected bool $continuousMatching = false;

    protected ?Inbox $fallback = null;

    public function add(Inbox $inbox): self
    {
        $this->inboxes[] = $inbox;

        return $this;
    }

    /**
     * @param InboundEmail $email
     * @throws Exception
     */
    public function run(InboundEmail $email): void
    {
        $matchedAny = false;
        $inboxes = collect($this->inboxes)->sortByDesc('priority');

        /**
         * @var $inbox Inbox
         */
        foreach ($inboxes as $inbox) {
            $matched = $inbox->run($email);

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
         * @var Inbox $inbox
         */
        $inbox = app(Inbox::class);
        $inbox->action($action);

        $this->fallback = $inbox;

        return $this;
    }

    public function continuousMatching(): self
    {
        $this->continuousMatching = true;

        return $this;
    }
}
