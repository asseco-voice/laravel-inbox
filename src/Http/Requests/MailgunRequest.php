<?php

declare(strict_types=1);

namespace BeyondCode\Mailbox\Http\Requests;

use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;

class MailgunRequest extends MailboxRequest
{
    public function validator()
    {
        $validator = Validator::make($this->all(), [
            'body-mime' => 'required',
            'timestamp' => 'required',
            'token' => 'required',
            'signature' => 'required',
        ]);

        $validator->after(function () {
            $this->verifySignature();
        });

        return $validator;
    }

    protected function verifySignature(): void
    {
        $data = $this->request->get('timestamp').$this->request->get('token');

        $signature = hash_hmac('sha256', $data, config('mailbox.services.mailgun.key') ?: '');

        $signed = hash_equals($this->request->get('signature'), $signature);

        abort_unless($signed && $this->isFresh($this->request->get('timestamp')), 401, 'Invalid Mailgun signature or timestamp.');
    }

    protected function isFresh($timestamp): bool
    {
        return now()->subMinutes(2)->lte(Carbon::createFromTimestamp($timestamp));
    }
}
