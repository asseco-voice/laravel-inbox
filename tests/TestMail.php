<?php

namespace Asseco\Inbox\Tests;

use Illuminate\Mail\Mailable;

class TestMail extends Mailable
{
    public function build()
    {
        $this->from('example@asseco-see.hr')
            ->subject('This is a subject')
            ->html('<html>Example email content</html>');
    }
}
