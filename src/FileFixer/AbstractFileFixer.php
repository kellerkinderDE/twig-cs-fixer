<?php

declare(strict_types=1);

namespace Kellerkinder\TwigCsFixer\FileFixer;

use Kellerkinder\TwigCsFixer\Config;
use Kellerkinder\TwigCsFixer\File;

abstract class AbstractFileFixer
{
    abstract public function fix(Config $config, File $file): void;

    abstract public function getRuleName(): string;

    public function isActive(array $rules): bool
    {
        return in_array($this->getRuleName(), $rules);
    }
}
