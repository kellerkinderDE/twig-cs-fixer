<?php

declare(strict_types=1);

namespace Kellerkinder\TwigCsFixer\Fixer;

use Kellerkinder\TwigCsFixer\Violations\AbstractViolation;
use Kellerkinder\TwigCsFixer\Violations\TrailingSpaceViolation;

class TrailingSpaceFixer extends AbstractFixer
{
    public function supports(AbstractViolation $violation): bool
    {
        return $violation instanceof TrailingSpaceViolation;
    }

    /**
     * @param TrailingSpaceViolation $violation
     */
    public function fix(AbstractViolation $violation): void
    {
        $fileContent = [];

        if (empty($fileContent)) {
            $fileContent = $this->getFileContent($violation);

            if (empty($fileContent)) {
                return;
            }
        }

        $violationLine               = $violation->getLine() - 1;
        $oldLine                     = $fileContent[$violationLine];
        $fileContent[$violationLine] = preg_replace('/[[:blank:]]+$/', '$1', $fileContent[$violationLine]);

        if ($oldLine !== $fileContent[$violationLine]) {
            $violation->setFixed(true);
        }

        if (!empty($fileContent)) {
            $file = implode(PHP_EOL, $fileContent);
            file_put_contents($violation->getPath(), $file);
        }
    }
}
