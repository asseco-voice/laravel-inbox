<?php

declare(strict_types=1);

namespace Asseco\Inbox\Http\Requests;

use Asseco\Inbox\InboundEmail;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator;

class InboxRequest extends FormRequest
{
    public function validator()
    {
        return Validator::make($this->all(), [
            'email' => 'required',
        ]);
    }

    public function email(): InboundEmail
    {
        /** @var InboundEmail $modelClass */
        $modelClass = config('asseco-inbox.model');

        return $modelClass::fromMessage($this->get('email'));
    }
}
