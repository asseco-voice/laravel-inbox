<?php

declare(strict_types=1);

namespace Asseco\Inbox;

use Asseco\Inbox\Contracts\CanMatch;
use Carbon\Carbon;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;

class InboundEmail extends Model implements CanMatch
{
    protected Email $emailMessage;

    protected $fillable = [
        'message',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->message_id = $model->id();
        });
    }

    public static function fromMessage(Email $message): self
    {
        /**
         * @var InboundEmail $inbound
         */
        $inbound = self::query()->make([
            'message' => $message,
        ]);

        return $inbound;
    }

    public function id(): string
    {
        return $this->message()->getHeaders()->getHeaderBody('Message-Id') ?: Str::random();
    }

    public function replyId(): ?string
    {
        return $this->message()->getHeaders()->getHeaderBody('In-Reply-To') ?: null;
    }

    public function date(): Carbon
    {
        return Carbon::make($this->message()->getHeaders()->getHeaderBody('Date'));
    }

    public function subject(): ?string
    {
        return $this->message()->getHeaders()->getHeaderBody('Subject');
    }

    public function from(): string
    {
        $from = $this->extractAddress(
            $this->message()->getHeaders()->getHeaderBody('From')
        );

        return Arr::get($from, 0, '');
    }

    public function fromName(): string
    {
        $fromArray = $this->message()->getHeaders()->getHeaderBody('From');
        $from = Arr::get($fromArray, 0);

        if (!$from instanceof Address) {
            return '';
        }

        return $from->getName();
    }

    public function to(): array
    {
        return $this->extractAddress(
            $this->message()->getHeaders()->getHeaderBody('To')
        );
    }

    public function cc(): array
    {
        return $this->extractAddress(
            $this->message()->getHeaders()->getHeaderBody('Cc')
        );
    }

    public function bcc(): array
    {
        return $this->extractAddress(
            $this->message()->getHeaders()->getHeaderBody('Bcc')
        );
    }

    public function headerValue(string $header): ?string
    {
        return $this->message()->getHeaders()->getHeaderBody($header) ?: null;
    }

    /**
     * @return array|DataPart[]
     */
    public function attachments(): array
    {
        return $this->message()->getAttachments();
    }

    public function message(): Email
    {
        $this->emailMessage = $this->message;

        return $this->emailMessage;
    }

    public function reply(Mailable $mailable)
    {
        if ($mailable instanceof \Illuminate\Mail\Mailable) {
            $mailable->withSymfonyMessage(function (Email $message) {
                $message->getHeaders()->addIdHeader('In-Reply-To', $this->id());
            });
        }

        Mail::to($this->from())->send($mailable);
    }

    public function forward($recipients)
    {
        Mail::send([], [], function ($message) use ($recipients) {
            $message
                ->to($recipients)
                ->subject($this->subject())
                ->html($this->body());
        });
    }

    public function body(): ?string
    {
        return $this->message()->getBody()->toString();
    }

    public function getMatchedValues(string $matchBy): array
    {
        return match ($matchBy) {
            Pattern::FROM => [$this->from()],
            Pattern::TO => $this->to(),
            Pattern::CC => $this->cc(),
            Pattern::BCC => $this->bcc(),
            Pattern::SUBJECT => [$this->subject()],
            default => [],
        };
    }

    protected function extractAddress($addresses): array
    {
        return collect($addresses)->map(function (Address $address) {
            return $address->getAddress();
        })->toArray();
    }
}
