<?php

declare(strict_types=1);

namespace Asseco\Inbox\Http\Requests;

use Asseco\Inbox\InboundEmail;
use Illuminate\Foundation\Http\FormRequest;

class EmailRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'email' => 'required',
        ];
    }

    public function email(): InboundEmail
    {
        return new InboundEmail($this->get('email'));
    }
}
