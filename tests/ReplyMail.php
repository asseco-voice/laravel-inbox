<?php

namespace Asseco\Inbox\Tests;

use Illuminate\Mail\Mailable;

class ReplyMail extends Mailable
{
    public function build()
    {
        $this->from('someone@asseco-see.hr')
            ->subject('This is my reply')
            ->html('Hi!');
    }
}
