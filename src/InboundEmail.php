<?php

declare(strict_types=1);

namespace Asseco\Inbox;

use Asseco\Inbox\Contracts\CanMatch;
use Carbon\Carbon;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Symfony\Component\Mime\Email;
use ZBateson\MailMimeParser\Header\AddressHeader;
use ZBateson\MailMimeParser\Header\Part\AddressPart;
use ZBateson\MailMimeParser\Message as MimeMessage;

class InboundEmail extends Model implements CanMatch
{
    public MimeMessage $message;

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

    public static function fromMessage(string $message): self
    {
        /**
         * @var InboundEmail $inbound
         */
        $inbound = self::query()->make([
            'message' => MimeMessage::from($message, true)
        ]);

        return $inbound;
    }

    public function id(): string
    {
        return $this->message->getHeaderValue('Message-Id', Str::random());
    }

    public function replyId(): ?string
    {
        return $this->message->getHeaderValue('In-Reply-To');
    }

    public function date(): Carbon
    {
        return Carbon::make($this->message->getHeaderValue('Date'));
    }

    public function subject(): ?string
    {
        return $this->message->getHeaderValue('Subject');
    }

    public function from(): string
    {
        $from = $this->message->getHeader('From');

        if ($from instanceof AddressHeader) {
            return $from->getEmail();
        }

        return '';
    }

    public function fromName(): string
    {
        $from = $this->message->getHeader('From');

        if ($from instanceof AddressHeader) {
            return $from->getPersonName();
        }

        return '';
    }

    /**
     * @return AddressPart[]
     */
    public function to(): array
    {
        return $this->convertAddressHeader($this->message->getHeader('To'));
    }

    /**
     * @return AddressPart[]
     */
    public function cc(): array
    {
        return $this->convertAddressHeader($this->message->getHeader('Cc'));
    }

    /**
     * @return AddressPart[]
     */
    public function bcc(): array
    {
        return $this->convertAddressHeader($this->message->getHeader('Bcc'));
    }

    protected function convertAddressHeader($header): array
    {
        if ($header instanceof AddressHeader) {
            return collect($header->getAddresses())->toArray();
        }

        return [];
    }

    public function attachments(): array
    {
        return $this->message->getAllAttachmentParts();
    }

    public function reply(Mailable $mailable)
    {
        if ($mailable instanceof \Illuminate\Mail\Mailable) {
            $mailable->withSymfonyMessage(function (Email $message) {
                $message->getHeaders()->addIdHeader('In-Reply-To', $this->id());
            });
        }

        return Mail::to($this->from())->send($mailable);
    }

    public function forward($recipients)
    {
        Mail::send([], [], function ($message) use ($recipients) {
            $message->to($recipients)
                ->subject($this->subject())
                ->setBody($this->body(), $this->message->getContentType());
        });
    }

    public function body(): ?string
    {
        return $this->isHtml() ? $this->html() : $this->text();
    }

    public function isHtml(): bool
    {
        return !empty($this->html());
    }

    public function isText(): bool
    {
        return !empty($this->text());
    }

    public function text(): ?string
    {
        return $this->message->getTextContent();
    }

    public function html(): ?string
    {
        return $this->message->getHtmlContent();
    }

    public function isValid(): bool
    {
        return $this->from() !== '' && ($this->isText() || $this->isHtml());
    }

    public function getMatchedValues(string $matchBy): array
    {
        return match ($matchBy) {
            Pattern::FROM => [$this->from()],
            Pattern::TO => $this->convertMessageAddresses($this->to()),
            Pattern::CC => $this->convertMessageAddresses($this->cc()),
            Pattern::BCC => $this->convertMessageAddresses($this->bcc()),
            Pattern::SUBJECT => [$this->subject()],
            default => [],
        };
    }

    protected function convertMessageAddresses($addresses): array
    {
        return collect($addresses)->map(function (AddressPart $address) {
            return $address->getEmail();
        })->toArray();
    }
}
