<?php

declare(strict_types=1);

namespace Kellerkinder\TwigCsFixer\MatchFixer;

use function is_string;
use Kellerkinder\TwigCsFixer\Config;
use Kellerkinder\TwigCsFixer\Match;
use Kellerkinder\TwigCsFixer\Violations\PipeSuffixSpacingViolation;

class PipeSuffixSpacingFixer extends AbstractMatchFixer
{
    public const VIOLATION_REGEX = '/\|[[:blank:]]+/';
    public const REPLACEMENT     = '|';

    public static function getRuleName(): string
    {
        return 'PipeSuffixSpacing';
    }

    public function fix(Config $config, Match $match): void
    {
        if (!$this->isTwigMatch($match->getMatch())) {
            return;
        }

        $line             = $match->getFixedMatch();
        $violationMatches = [];
        preg_match_all(self::VIOLATION_REGEX, $line, $violationMatches);

        if (!empty($violationMatches)) {
            foreach ($violationMatches[0] as $violationMatch) {
                $column = strpos($match->getFixedMatch(), $violationMatch);

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
