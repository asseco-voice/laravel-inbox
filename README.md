<p align="center"><a href="https://see.asseco.com" target="_blank"><img src="https://github.com/asseco-voice/art/blob/main/evil_logo.png" width="500"></a></p>

# Laravel inbox

Purpose of this package is to provide pattern-matching for incoming 
communication and executing custom callbacks if they match.

Credits to [BeyondCode](https://github.com/beyondcode/laravel-mailbox) for the
initial codebase. The idea for this package later on substantially diverged 
from the original, leaving no alternative than to separate it as a new package.

## Installation

Require the package with ``composer require asseco-voice/laravel-inbox``.
Service provider will be registered automatically.

## Usage

### Interface

Before you start using the package, you need to have a class implementing a ``CanMatch``
interface so that package knows what will it validate regex against. 

I.e. if you want to validate against 'from', you need to defined where to fetch
that piece of information from.

```php
class Message implements CanMatch
{
    ...
    public function getMatchedValues(string $matchBy): array
    {
        switch ($matchBy) {
            case 'from':                return [$this->from()];
            case 'subject':             return [$this->subject()];
            case 'something_custom':    return [$this->custom()];
            default:                    return [];
        }
    }
    ...
}
```

### Inbox API

``Inbox`` is a class in which you provide regex patterns which you'd like to be 
matched before the given callback is executed. 

I.e. the callback defined under `action()` will execute only if the `.*@gmail.com`
pattern is matched:

```php
$inbox = new Inbox();

$inbox
    ->from('{.*}@gmail.com')
    ->action(function (CanMatch $message) {
        Log::info("Message received");
    });
```

Patterns need to be surrounded within ``{ }``. 
 
- ``from($pattern)`` will target `from` field
- ``to($pattern)`` will target `to` field
- ``cc($pattern)`` will target `cc` field
- ``bcc($pattern)`` will target `bcc` field
- ``subject($pattern)`` will target `subject` field


- ```setPattern($name, $pattern)``` is created for every other use case 
you might need. I.e. ``from($pattern)`` is just a shorthand for 
`setPattern('from', $pattern)`.


- ``meta([...])`` for adding any other meta-data. 


- ``action(callable)`` is a callback to be executed and takes object implementing
a `CanMatch` interface as an only argument (can be omitted though).
 

- ``matchEither()`` will act as `OR` gate in case more than one pattern is defined.
Default behavior is to match *all* patterns for a callback to execute. 


- ``priority($number)`` will set inbox priority which will be taken into account
*only* if ``InboxGroup`` is used. 


- ``run(CanMatch $message)`` will take an instance of object implementing `CanMatch`
interface and will return a bool of whether the inbox was hit or not. **This method
must not be used when using inbox groups. They have their own `run()` method**.

### Inbox group API

Should you require multiple cases covered, there is also a higher level
concept - ``InboxGroup`` which acts as a container for multiple inboxes, 
having a few fluent API methods as well.

- ``add(Inbox $inbox)`` will add an inbox to a group
- ``continuousMatching()`` will continue matching after a first match is hit. Default
behavior is to stop after one inbox is matched. 
- ``fallback(callable)`` will add a (non-mandatory) fallback which will execute if no 
inbox is hit.
- ``run(CanMatch $message)`` will take an instance of object implementing `CanMatch`
interface and will (unlike inbox ``run()`` method) return an array of matched inboxes. 
Inboxes will be ran by priority.

# More examples

Examples will cover cases using mail, but it can be adapted to any incoming
communication.

Matching functions can be used to either provide an exact match (i.e. `from('exact@email.com')`)
or providing a regex match which needs to be surrounded with curly braces ``{ }`` to be interpreted
as such.

Example:
```php
$inbox = new Inbox();

$inbox
    ->from('{.*}@gmail.com')
    ->to('{.*}@gmail.com')
    ->subject('Subject to match')
    ->action(function (CanMatch $email) {
        Log::info("Mail received");
    })
    ->matchEither()
    ->priority(10);
```

More examples with outcomes:
- having an exact match `from('your.mail@gmail.com')`:
  - only mails coming solely from `your.mail@gmail.com` will be matched.
- having a partial match `from('{.*}@gmail.com')`:
  - any gmail address will be matched like `someone@gmail.com` and `someone-else@gmail.com` will be 
  matched, but `someone@yahoo.com` won't).
- having a partial match `from('your.name@{.*}')`:
  - same as last example, but this time the name is fixed and provider is flexible.
   It would match `your.name@gmail.com`, `your.name@yahoo.com`, but it wouldn't match
   ``not.your.name@gmail.com``.
- having a full regex match: `from('{.*}')`:
  - accepts anything.

Group example:
```php
public function receiveEmail($email){

    $inbox1 = ...;
    $inbox2 = ...;
    $inbox3 = ...;

    $group = new InboxGroup(); 
    
    $group
        ->add($inbox1)
        ->add($inbox2)
        ->add($inbox3) 
        ->fallback(function (CanMatch $email) {
            Log::info("Fell back");
        })
        ->continuousMatching()
        ->run($email);
}
```

If you don't want to use groups, but a single inbox, you can call run method on it directly:

```php
public function receiveEmail($email){

    $inbox = new Inbox();
    
    $inbox
        ->...
        ->...
        ->run($email);
}
```
