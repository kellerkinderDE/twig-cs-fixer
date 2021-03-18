<?php

declare(strict_types=1);

namespace Kellerkinder\TwigCsFixer\Fixer;

use Kellerkinder\TwigCsFixer\Violations\AbstractViolation;
use Kellerkinder\TwigCsFixer\Violations\PipePrefixSpacingViolation;
use Kellerkinder\TwigCsFixer\Violations\TrailingSpaceViolation;

class PipePrefixSpacingFixer extends AbstractFixer
{
    public function supports(AbstractViolation $violation): bool
    {
        return $violation instanceof PipePrefixSpacingViolation;
    }

    /**
     * @param TrailingSpaceViolation $violation
     */
    public function fix(AbstractViolation $violation): void
    {
    }
}
