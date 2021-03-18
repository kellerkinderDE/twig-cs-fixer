<?php

declare(strict_types=1);

namespace Kellerkinder\TwigCsFixer\Fixer;

use Kellerkinder\TwigCsFixer\Violations\AbstractViolation;

abstract class AbstractFixer
{
    abstract public function supports(AbstractViolation $violation): bool;

    abstract public function fix(AbstractViolation $violation): void;

    protected function getFileContent(AbstractViolation $violation): array
    {
        $fileContent = file_get_contents($violation->getPath());

        if (!$fileContent || empty($fileContent)) {
            return [];
        }

        $file = explode(PHP_EOL, $fileContent);

        if (!$file) {
            return [];
        }

        return $file;
    }
}
