<?php

declare(strict_types=1);

namespace Kellerkinder\TwigCsFixer\Validator;

use Kellerkinder\TwigCsFixer\File;
use Kellerkinder\TwigCsFixer\Match;

abstract class AbstractValidator
{
    public function validate(File $file): void
    {
        foreach ($file->getMatches() as $match) {
            $hasViolation = $this->validateMatch($match);

            if ($hasViolation) {
                $file->setViolation(true);
            }
        }
    }

    abstract protected function validateMatch(Match $match): bool;

    /**
     * Check if line only contains twig
     */
    protected function isTwigMatch(string $line): bool
    {
        return (bool) preg_match('/^{{.+/', $line) && !preg_match('/^{%.+/', $line);
    }
}
