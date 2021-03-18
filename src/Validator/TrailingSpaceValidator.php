<?php

declare(strict_types=1);

namespace Kellerkinder\TwigCsFixer\Validator;

use Kellerkinder\TwigCsFixer\Match;
use Kellerkinder\TwigCsFixer\Violations\SpaceLineViolation;
use Kellerkinder\TwigCsFixer\Violations\TrailingSpaceViolation;

class TrailingSpaceValidator extends AbstractValidator
{
    private const PREG = '/^.+[[:blank:]]+$/';

    /**
     * @return SpaceLineViolation[]
     */
    protected function validateMatch(Match $match): bool
    {
        if (!$this->isTwigMatch($match->getMatch())) {
            return false;
        }

        $violationMatches = [];
        $line             = $match->getMatch();
        preg_match_all(self::PREG, $match->getMatch(), $violationMatches);

        if ($violationMatches !== false && !empty($violationMatches[0])) {
            foreach ($violationMatches[0] as $offset => $violationMatch) {
                $column = strpos($line, $violationMatch, $offset);

                if (!$column) {
                    return false;
                }

                $match->addViolation(
                    new TrailingSpaceViolation($column)
                );
            }

            return true;
        }

        return false;
    }
}
