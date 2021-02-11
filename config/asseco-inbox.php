<?php

use Asseco\Inbox\InboundEmail;

return [

    /*
     * The model class to use when converting an incoming email to a message.
     * It must extend the default model class
     */
    'model'      => InboundEmail::class,

    /*
     * Some services do not have their own authentication methods to
     * verify the incoming request. For these services, you need
     * to use this username and password combination for HTTP
     * basic authentication.
     *
     * See the driver specific documentation if it applies to your
     * driver.
     */
    'basic_auth' => [
        'username' => env('MAILBOX_HTTP_USERNAME', 'laravel-mailbox'),
        'password' => env('MAILBOX_HTTP_PASSWORD'),
    ],

    /*
     * Third party service configuration.
     */
    'services'   => [

        'mailgun' => [
            'key' => env('MAILBOX_MAILGUN_KEY'),
        ],

    ],

];
