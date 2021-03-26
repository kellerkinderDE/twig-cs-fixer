<?php

declare(strict_types=1);

namespace Kellerkinder\TwigCsFixer\Fixer;

use Kellerkinder\TwigCsFixer\Match;
use Kellerkinder\TwigCsFixer\Violations\TrailingSpaceViolation;

class TrailingSpaceFixer extends AbstractFixer
{
    public const VIOLATION_REGEX = '/\S+[[:blank:]]+\Z/';
    public const FIX_REGEX       = '/\S+/';

    public function fix(Match $match): void
    {
        $violationMatches = [];
        $line             = $match->getMatch();
        preg_match_all(self::VIOLATION_REGEX, $match->getMatch(), $violationMatches);

        if ($violationMatches !== false && !empty($violationMatches[0])) {
            foreach ($violationMatches[0] as $offset => $violationMatch) {
                $column = strpos($line, $violationMatch, $offset);

                if (!$column) {
                    return;
                }

                $fixedMatch     = $match->getFixedMatch();
                $violatedSubstr = substr($fixedMatch, $column);
                $replacer       = [];
                preg_match(self::FIX_REGEX, $violatedSubstr, $replacer);

                if ($replacer !== false && !empty($replacer[0])) {
                    $fixedSubstr = str_replace($violationMatch, $replacer[0], $violatedSubstr);

                    if ($fixedSubstr !== null) {
                        $match->setFixedMatch(str_replace($violatedSubstr, $fixedSubstr, $fixedMatch));
                    }
                }
                $match->addViolation(
                    new TrailingSpaceViolation($column)
                );
            }
        }
    }
}
