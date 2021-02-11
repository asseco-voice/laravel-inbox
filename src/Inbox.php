<?php

declare(strict_types=1);

namespace Asseco\Inbox;

use Asseco\Inbox\Contracts\Message;
use Asseco\Inbox\Traits\HandlesParameters;
use Asseco\Inbox\Traits\HandlesRegularExpressions;
use Exception;
use Illuminate\Routing\RouteDependencyResolverTrait;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\ForwardsCalls;
use ReflectionFunction;

class Inbox
{
    use HandlesParameters,
        HandlesRegularExpressions,
        RouteDependencyResolverTrait,
        ForwardsCalls;

    protected $action;

    protected array $matches = [];

    protected array $wheres = [];

    protected array $patterns = [];

    protected int $priority = 0;

    protected bool $matchEither = false;

    public function from(string $regex): self
    {
        $this->setPattern(Pattern::FROM, $regex);

        return $this;
    }

    public function to(string $regex): self
    {
        $this->setPattern(Pattern::TO, $regex);

        return $this;
    }

    public function cc(string $regex): self
    {
        $this->setPattern(Pattern::CC, $regex);

        return $this;
    }

    public function bcc(string $regex): self
    {
        $this->setPattern(Pattern::BCC, $regex);

        return $this;
    }

    public function subject(string $regex): self
    {
        $this->setPattern(Pattern::SUBJECT, $regex);

        return $this;
    }

    protected function setPattern(string $matchBy, string $pattern): void
    {
        $this->patterns[] = new Pattern($matchBy, $pattern);
    }

    public function matchEither(bool $match = true): self
    {
        $this->matchEither = $match;

        return $this;
    }

    public function action($action): self
    {
        $this->action = $action;

        return $this;
    }

    public function priority(int $priority): self
    {
        $this->priority = $priority;

        return $this;
    }

    public function run(Message $message): bool
    {
        if (!$message->isValid()) {
            throw new Exception('Message invalid.');
        }

        if (!$this->matchFound($message)) {
            return false;
        }

        $this->isCallable() ?
            $this->runCallable($message) : $this->runClass($message);

        return true;
    }

    protected function matchFound(Message $message): bool
    {
        $matchedPatterns = $this->filterPatterns($message);

        return $this->matchEither ?
            $this->isPartialMatch($matchedPatterns) :
            $this->isFullMatch($matchedPatterns);
    }

    protected function filterPatterns(Message $message): Collection
    {
        return collect($this->patterns)->filter(function (Pattern $pattern) use ($message) {
            $matchedValues = $message->getMatchedValues($pattern->matchBy);

            return $this->valueMatchesRegex($matchedValues, $pattern->regex) !== null;
        });
    }

    protected function valueMatchesRegex(array $matchValues, string $regex): ?string
    {
        return collect($matchValues)->first(function (string $matchValue) use ($regex) {
            return $this->matchesRegularExpression($matchValue, $regex);
        });
    }

    protected function isPartialMatch(Collection $matchedPatterns): bool
    {
        return $matchedPatterns->isNotEmpty();
    }

    protected function isFullMatch(Collection $matchedPatterns): bool
    {
        return count($this->patterns) == $matchedPatterns->count();
    }

    protected function isCallable(): bool
    {
        if (!$this->action) {
            throw new Exception('Inbox needs to have an action defined.');
        }

        return is_callable($this->action);
    }

    protected function runCallable(Message $email)
    {
        $callable = $this->action;

        $parameters = $this->resolveMethodDependencies(
            [$email] + $this->parametersWithoutNulls(), new ReflectionFunction($this->action)
        );

        return $callable(...array_values($parameters));
    }

    protected function runClass(Message $email)
    {
        $method = $this->getInboxMethod();
        $inbox = $this->getInbox();

        $parameters = $this->resolveClassMethodDependencies(
            [$email] + $this->parametersWithoutNulls(), $inbox, $method
        );

        return $inbox->{$method}(...array_values($parameters));
    }

    protected function getInbox(): Inbox
    {
        $class = $this->parseInboxCallback()[0];

        return app()->make(ltrim($class, '\\'));
    }

    protected function getInboxMethod(): string
    {
        return $this->parseInboxCallback()[1] ?? '__invoke';
    }

    protected function parseInboxCallback(): array
    {
        return Str::parseCallback($this->action);
    }
}
