<?php

declare(strict_types=1);

namespace Kellerkinder\TwigCsFixer\MatchFixer;

use Kellerkinder\TwigCsFixer\Config;
use Kellerkinder\TwigCsFixer\Match;

abstract class AbstractMatchFixer
{
    abstract public function fix(Config $config, Match $match): void;

    abstract public function getRuleName(): string;

    public function isActive(array $rules): bool
    {
        return in_array($this->getRuleName(), $rules);
    }

    /**
     * Check if line only contains twig
     */
    protected function isTwigMatch(string $line): bool
    {
        return (bool) preg_match('/^{{.+/', $line) && !preg_match('/^{%.+/', $line);
    }
}
