<?php

declare(strict_types=1);

namespace Asseco\Inbox\Http\Middleware;

use Closure;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class MailboxBasicAuthentication
{
    public function handle($request, Closure $next)
    {
        $user = $request->getUser();
        $password = $request->getPassword();

        if (($user === config('asseco-inbox.basic_auth.username') && $password === config('asseco-inbox.basic_auth.password'))) {
            return $next($request);
        }

        throw new UnauthorizedHttpException('Laravel Mailbox');
    }
}
