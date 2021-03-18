<?php

declare(strict_types=1);

namespace Kellerkinder\TwigCsFixer\Validator;

use Kellerkinder\TwigCsFixer\Match;
use Kellerkinder\TwigCsFixer\Violations\PipePrefixSpacingViolation;

class PipeSuffixSpacingValidator extends AbstractValidator
{
    private const PREG = '/[[:blank:]]+\|/';

    protected function validateMatch(Match $match): bool
    {
        if (!$this->isTwigMatch($match->getMatch())) {
            return false;
        }

        $violationMatches = [];
        $line             = $match->getMatch();
        preg_match_all(self::PREG, $match->getMatch(), $violationMatches);

        if (!empty($violationMatches)) {
            foreach ($violationMatches[0] as $offset => $violationMatch) {
                $column = strpos($line, $violationMatch, $offset);

                if (!$column) {
                    return false;
                }

                $match->addViolation(
                    new PipePrefixSpacingViolation($column)
                );
            }

            return true;
        }

        return false;
    }
}
