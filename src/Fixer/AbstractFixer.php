<?php

declare(strict_types=1);

namespace Kellerkinder\TwigCsFixer\Fixer;

use Kellerkinder\TwigCsFixer\Match;

abstract class AbstractFixer
{
    abstract public function fix(Match $match): void;

    /**
     * Check if line only contains twig
     */
    protected function isTwigMatch(string $line): bool
    {
        return (bool) preg_match('/^{{.+/', $line) && !preg_match('/^{%.+/', $line);
    }
}
