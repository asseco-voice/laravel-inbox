<?php

namespace Asseco\Mailbox\Tests\Controllers;

use Asseco\Mailbox\Http\Controllers\PostmarkController;
use Asseco\Mailbox\Tests\TestCase;
use Illuminate\Support\Facades\Route;

class PostmarkTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        Route::post('/laravel-mailbox/postmark', PostmarkController::class);
    }

    /** @test */
    public function it_expects_to_receive_raw_email_field()
    {
        $this->withoutMiddleware();

        $this->post('/laravel-mailbox/postmark', [
            'something' => 'value',
        ])->assertSessionHasErrors('RawEmail')->assertStatus(302);

        $this->post('/laravel-mailbox/postmark', [
            'RawEmail' => 'value',
        ])->assertStatus(200);
    }
}
