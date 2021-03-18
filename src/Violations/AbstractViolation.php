<?php

declare(strict_types=1);

namespace Kellerkinder\TwigCsFixer\Violations;

abstract class AbstractViolation
{
    /** @var int */
    private $column;

    /** @var bool */
    private $fixed = false;

    public function __construct(int $column)
    {
        $this->column = $column;
    }

    public function getColumn(): int
    {
        return $this->column;
    }

    public function isFixed(): bool
    {
        return $this->fixed;
    }

    public function setFixed(bool $fixed): void
    {
        $this->fixed = $fixed;
    }
}
