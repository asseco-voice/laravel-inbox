<?php

declare(strict_types=1);

namespace Asseco\Inbox;

use Asseco\Inbox\Contracts\CanMatch;
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
     * @param CanMatch $message
     * @return array
     * @throws Exception
     */
    public function run(CanMatch $message): array
    {
        $inboxes = collect($this->inboxes)->sortByDesc('priority');

        $matchedInboxes = [];

        /**
         * @var $inbox Inbox
         */
        foreach ($inboxes as $inbox) {
            $matched = $inbox->run($message);

            if (!$matched) {
                continue;
            }

            $matchedInboxes[] = $inbox;

            if (!$this->continuousMatching) {
                break;
            }
        }

        if (empty($matchedInboxes) && $this->fallback !== null) {
            $this->fallback->run($message);

            return [$this->fallback];
        }

        return $matchedInboxes;
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
