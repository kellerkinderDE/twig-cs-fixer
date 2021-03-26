<?php

declare(strict_types=1);

namespace Kellerkinder\TwigCsFixer\MatchFixer;

use function is_string;
use Kellerkinder\TwigCsFixer\Match;
use Kellerkinder\TwigCsFixer\Violations\PipeSuffixSpacingViolation;

class PipePrefixSpacingFixer extends AbstractMatchFixer
{
    public const VIOLATION_REGEX = '/\|[[:blank:]]+/';
    public const REPLACEMENT     = '|';

    public function fix(Match $match): void
    {
        if (!$this->isTwigMatch($match->getMatch())) {
            return;
        }

        $violationMatches = [];
        $line             = $match->getMatch();
        preg_match_all(self::VIOLATION_REGEX, $match->getMatch(), $violationMatches);

        if (!empty($violationMatches)) {
            foreach ($violationMatches[0] as $violationMatch) {
                $column = strpos($line, $violationMatch);

                if (!$column) {
                    return;
                }

                $fixedMatch     = $match->getFixedMatch();
                $violatedSubstr = substr($fixedMatch, $column);
                $fixedSubstr    = preg_replace(self::VIOLATION_REGEX, self::REPLACEMENT, $violatedSubstr, 1);

                if (is_string($fixedSubstr)) {
                    $match->setFixedMatch(str_replace($violatedSubstr, $fixedSubstr, $fixedMatch));
                    $match->addViolation(new PipeSuffixSpacingViolation($column));
                }
            }
        }
    }
}
