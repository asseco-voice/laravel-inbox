<?php

declare(strict_types=1);

namespace Asseco\Inbox\Http\Requests;

use Illuminate\Support\Facades\Validator;

class PostmarkRequest extends EmailRequest
{
    public function validator()
    {
        return Validator::make($this->all(), [
            'RawEmail' => 'required',
        ]);
    }
}
