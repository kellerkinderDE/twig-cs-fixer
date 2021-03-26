<?php

declare(strict_types=1);

namespace Kellerkinder\TwigCsFixer\Fixer;

use Kellerkinder\TwigCsFixer\Match;
use Kellerkinder\TwigCsFixer\Violations\AbstractViolation;
use Kellerkinder\TwigCsFixer\Violations\SpaceLineViolation;

class SpaceLineFixer extends AbstractFixer
{
    private const VIOLATION_REGEX = '/^.{0}[[:blank:]]+$/';

    public function supports(AbstractViolation $violation): bool
    {
        return $violation instanceof SpaceLineViolation;
    }

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

                $match->setFixedMatch('');
                $match->addViolation(new SpaceLineViolation($column));
            }
        }
    }
}
