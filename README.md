<p align="center"><a href="https://see.asseco.com" target="_blank"><img src="https://github.com/asseco-voice/art/blob/main/evil_logo.png" width="500"></a></p>

# Laravel inbox

Purpose of this package is to provide pattern matching for incoming 
communication and executing custom callbacks if they match.

Credits to [BeyondCode](https://github.com/beyondcode/laravel-mailbox) for
initial codebase. The idea for this package later on substantially diverged 
from the original, leaving no alternative than to separate it as a new package.

## Installation

Require the package with ``composer require asseco-voice/laravel-inbox``.
Service provider will be registered automatically.

## Concept

``Inbox`` is the main entity of the package providing a fluent API to 
define set of patterns to match and how they interconnect, together 
with a callback function which will be executed on a successful match.

Should you require multiple cases covered, there is also a higher level
concept ``InboxGroup`` which acts as a container for multiple inboxes, 
having a few fluent API methods as well.

Examples will cover cases using mail, but it can be adapted to any incoming
communication.

Example:
```php
$inbox = new Inbox();

$inbox
    ->from('{user}@gmail.com')
    ->to('{user2}@gmail.com')
    ->subject('Subject to match')
    ->where('user', '.*')
    ->where('user2', '.*')
    ->action(function (InboundEmail $email) {
        Log::info("Mail received");
    })
    ->matchEither()
    ->priority(10);

```

Matching functions can be used to either provide an exact match (i.e. `from('exact@email.com')`)
or providing a regex match, in which case you need to define a ``where()`` clause as well
providing what will the keyword look for when validating:

```php
$inbox
    ->from('{user}@{service}.com')
    ->where('user', '.*')
    ->where('service', '.*');
```

By default, all patterns need to be matched (`AND` match) in order for action to be executed. 
So if you provide `from` and `to`, they both need to match. This can be changed 
by including a `matchEither()` function which will transform this logic to 
matching at least one (`OR` match). 

More examples with outcomes:
- having an exact match `from('your.mail@gmail.com')`:
  - only mails coming solely from `your.mail@gmail.com` will be matched.
- having a partial match `from('{pattern}@gmail.com')` with `.*` pattern:
  - any gmail address will be matched like `someone@gmail.com` and `someone-else@gmail.com` will be 
  matched, but `someone@yahoo.com` won't).
- having a partial match `from('your.name@{provider}')` with `.*` pattern:
  - same as last example, but this time the name is fixed and provider is flexible.
   It would match `your.name@gmail.com`, `your.name@yahoo.com`, but it wouldn't match
   ``not.your.name@gmail.com``.
- having a full regex match: `from('{pattern}')` with `.*` pattern:
  - accepts anything.

That being said, it is perfectly valid to stack several clauses together, but
in case of using the same method (i.e. ``from()``), be sure to use `matchEither()`
so that ``OR`` matching is triggered. 

```php
$mailbox
    ->from('{pattern}@gmail.com')
    ->from('your.name@{provider}')
    ->where('pattern', '[A-Z]+')
    ->where('provider', 'yahoo.*')
    ->matchEither();
```

Due to the fact that multiple inboxes can exist now, `InboxGroup` is responsible for 
running them. Executing `InboxGroup::run($email)` will run all the inboxes. Prior to 
executions it will order them by given `priority()`. `Inbox` has a default priority 
of `0`. The bigger the number, the sooner it is executed. 

Group example:
```php
$group = new InboxGroup(); 

$group
    ->add($inbox)
    ->add($inbox2)
    ->add($inbox3) 
    ->fallback(function (InboundEmail $email) {
        Log::info("Fell back");
    })
    ->continuousMatching()
    ->run($email);
```

You can define a fallback on your `InboxGroup` so that if none of the inboxes match, 
fallback will be executed. 

Inboxes will, by default, stop at first match. Meaning, if out 
of 5 inboxes, second one matches the incoming mail, execution will stop. This can 
be overridden including the `continuousMatching()` function which will run 
through all inboxes. So if match is found in 3/5 inboxes, those 3 callbacks will be executed. 
