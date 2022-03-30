<?php

declare(strict_types=1);

namespace Asseco\Inbox;

use Asseco\Inbox\Contracts\CanMatch;
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

    protected array $meta = [];

    protected array $matches = [];

    protected array $patterns = [];

    protected int $priority = 0;

    protected bool $matchEither = false;

    /**
     * @param  string  $regex
     * @return $this
     *
     * @throws Exception
     */
    public function from(string $regex): self
    {
        $this->setPattern(Pattern::FROM, $regex);

        return $this;
    }

    /**
     * @param  string  $regex
     * @return $this
     *
     * @throws Exception
     */
    public function to(string $regex): self
    {
        $this->setPattern(Pattern::TO, $regex);

        return $this;
    }

    /**
     * @param  string  $regex
     * @return $this
     *
     * @throws Exception
     */
    public function cc(string $regex): self
    {
        $this->setPattern(Pattern::CC, $regex);

        return $this;
    }

    /**
     * @param  string  $regex
     * @return $this
     *
     * @throws Exception
     */
    public function bcc(string $regex): self
    {
        $this->setPattern(Pattern::BCC, $regex);

        return $this;
    }

    /**
     * @param  string  $regex
     * @return $this
     *
     * @throws Exception
     */
    public function subject(string $regex): self
    {
        $this->setPattern(Pattern::SUBJECT, $regex);

        return $this;
    }

    /**
     * If no shorthand functions are adequate (from, to, cc...) use
     * this one to set it manually.
     *
     * @param  string  $matchBy
     * @param  string  $pattern
     *
     * @throws Exception
     */
    public function setPattern(string $matchBy, string $pattern): void
    {
        $this->patterns[] = new Pattern($matchBy, $pattern);
    }

    public function meta(array $meta)
    {
        $this->meta = $meta;

        return $this;
    }

    public function getMeta()
    {
        return $this->meta;
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

    public function run(CanMatch $message): bool
    {
        if (!$this->matchFound($message)) {
            return false;
        }

        $this->isCallable() ?
            $this->runCallable($message) : $this->runClass($message);

        return true;
    }

    protected function matchFound(CanMatch $message): bool
    {
        $matchedPatterns = $this->getMatchedPatterns($message);

        return $this->matchEither ?
            $this->isPartialMatch($matchedPatterns) :
            $this->isFullMatch($matchedPatterns);
    }

    protected function getMatchedPatterns(CanMatch $message): Collection
    {
        return collect($this->patterns)
            ->filter(function (Pattern $pattern) use ($message) {

                // Get the actual message values which should be validated against regex.
                $values = $message->getMatchedValues($pattern->matchBy);

                $matched = $this->match($values, $pattern->regex);

                return $matched !== null;
            });
    }

    /**
     * Will match values against regex and stop on first match.
     *
     * I.e. if we have an email with list of CC's, it is enough to know that
     * only one of those values was matched to know that pattern is matched.
     *
     * @param  array  $values
     * @param  string  $regex
     * @return string|null
     */
    protected function match(array $values, string $regex): ?string
    {
        return collect($values)
            ->first(function (string $value) use ($regex) {
                return $this->matchesRegularExpression($value, $regex);
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

    protected function runCallable(CanMatch $message)
    {
        $callable = $this->action;

        // This will force 'message' to be the first callback parameter.
        // Whatever parameter is left will be attached after it.
        $parameters = $this->resolveMethodDependencies(
            [$message] + $this->parametersWithoutNulls(), new ReflectionFunction($this->action)
        );

        return $callable(...array_values($parameters));
    }

    protected function runClass(CanMatch $message)
    {
        $method = $this->getInboxMethod();
        $inbox = $this->getInbox();

        $parameters = $this->resolveClassMethodDependencies(
            [$message] + $this->parametersWithoutNulls(), $inbox, $method
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
