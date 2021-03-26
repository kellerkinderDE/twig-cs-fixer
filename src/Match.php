<?php

declare(strict_types=1);

namespace Kellerkinder\TwigCsFixer;

use Kellerkinder\TwigCsFixer\Violations\AbstractViolation;

class Match
{
    /** @var int */
    private $line;

    /** @var int */
    private $column;

    /** @var string */
    private $match;

    /** @var string */
    private $fixedMatch = '';

    /** @var AbstractViolation[] */
    private $violations = [];

    public function __construct(int $line, int $column, string $match)
    {
        $this->line   = $line;
        $this->column = $column;
        $this->match  = $match;
    }

    public function getLine(): int
    {
        return $this->line;
    }

    public function getRealLine(): int
    {
        return $this->line + 1;
    }

    public function getColumn(): int
    {
        return $this->column;
    }

    public function getRealColumn(): int
    {
        return $this->column + 1;
    }

    public function getMatch(): string
    {
        return $this->match;
    }

    public function getFixedMatch(): string
    {
        return strlen($this->fixedMatch) > 0 ? $this->fixedMatch : $this->match;
    }

    public function setFixedMatch(string $fixedMatch): void
    {
        $this->fixedMatch = $fixedMatch;
    }

    public function getViolations(): array
    {
        return $this->violations;
    }

    public function hasViolations(): bool
    {
        return count($this->getViolations()) > 0;
    }

    public function setViolations(array $violations): void
    {
        $this->violations = $violations;
    }

    public function addViolation(AbstractViolation $violation): void
    {
        $this->violations[] = $violation;
    }
}
