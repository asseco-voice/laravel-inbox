<?php

use BeyondCode\Mailbox\InboundEmail;

return [

    /*
     * The model class to use when converting an incoming email to a message.
     * It must extend the default model class
     */
    'model' => InboundEmail::class,

    /*
     * The amount of days that incoming emails should be stored in your
     * application. You can use the cleanup artisan command to
     * delete all older inbound emails on a regular basis.
     * Set to INF to disable the cleanup artisan command.
     */
    'store_incoming_emails_for_days' => 7,

    /*
     * By default, this package only stores incoming email messages
     * when they match one of your mailboxes. To store all incoming
     * messages, modify this value.
     */
    'only_store_matching_emails' => true,

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
    'services' => [

        'mailgun' => [
            'key' => env('MAILBOX_MAILGUN_KEY'),
        ],

    ],

];
