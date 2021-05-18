<?php

declare(strict_types=1);

namespace Asseco\Inbox\Traits;

use Exception;
use Symfony\Component\Routing\Route;

trait HandlesRegularExpressions
{
    protected function matchesRegularExpression(string $matchValue, string $regex): bool
    {
        $pattern = $this->getPattern($regex);

        preg_match($pattern, $matchValue, $matches);

        $this->matches = array_merge($this->matches, $matches);

        return (bool) $matches;
    }

    /**
     * Re-using Symfony's Route for pattern parsing.
     *
     * @param string $fullString
     * @return string
     * @throws Exception
     */
    protected function getPattern(string $fullString): string
    {
        preg_match_all('|{(.+?)}|', $fullString, $patterns);

        if (count($patterns) !== 2) {
            throw new Exception('Patterns incorrect.');
        }

        [$requirements, $fullString] = $this->replaceNames($patterns[1], $fullString);

        $route = new Route($fullString);

        $route->setRequirements($requirements);

        $fullString = $route->compile()->getRegex();

        $fullString = preg_replace('/^(#|{)\^\/(.*)/', '$1^$2', $fullString);
        $fullString = str_replace('>[^/]+)', '>.+)', $fullString);
        $fullString = str_replace('$#sD', '$#sDi', $fullString);
        $fullString = str_replace('$}sD', '$}sDi', $fullString);

        return $fullString;
    }

    /**
     * Replacing actual patterns with placeholders so Symfony can convert it.
     *
     * I.e.
     *
     * Input:  {.*}something
     *
     * Output: {p_1}something, where requirement is set as ['p_1' => '.*']
     *
     * @param array $patterns
     * @param string $fullString
     * @return array
     */
    protected function replaceNames(array $patterns, string $fullString): array
    {
        $patternName = 1;
        $requirements = [];

        foreach ($patterns as $pattern) {
            $quotedPattern = '/' . preg_quote($pattern, '/') . '/';

            // Must not begin with a number
            $symfonyFriendlyPatternName = "p_$patternName";

            $fullString = preg_replace($quotedPattern, $symfonyFriendlyPatternName, $fullString, 1);

            $requirements = array_merge($requirements, [
                $symfonyFriendlyPatternName => $pattern,
            ]);

            $patternName++;
        }

        return [$requirements, $fullString];
    }
}
