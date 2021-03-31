<?php

declare(strict_types=1);

namespace Kellerkinder\TwigCsFixer\FileFixer;

use Kellerkinder\TwigCsFixer\File;

abstract class AbstractFileFixer
{
    abstract public function fix(File $file): void;
}
